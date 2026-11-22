#!/usr/bin/env python3
"""THROWAWAY: actual HTTP/signed-snapshot lifecycle proof against server.php."""
import copy
import html
import http.cookiejar
import json
from pathlib import Path
import re
import sqlite3
import subprocess
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parent
BASE = 'http://127.0.0.1:18765'
DB = ROOT / '.runtime/prototype.sqlite'
DB.parent.mkdir(parents=True, exist_ok=True)
connection = sqlite3.connect(DB)
connection.executescript('''
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS permissions;
CREATE TABLE projects (id INTEGER PRIMARY KEY, name TEXT NOT NULL);
INSERT INTO projects VALUES (42, 'Apollo'), (77, 'Artemis');
CREATE TABLE permissions (id INTEGER PRIMARY KEY, allowed INTEGER NOT NULL, view_allowed INTEGER NOT NULL);
INSERT INTO permissions VALUES (1, 1, 1);
''')
connection.commit()
(ROOT / '.runtime/events.jsonl').write_text('')
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(path, payload=None, token=None):
    headers = {'Accept': 'application/json' if payload is not None else 'text/html'}
    if payload is not None:
        headers.update({'Content-Type': 'application/json', 'X-Livewire': 'true', 'X-CSRF-TOKEN': token})
    req = urllib.request.Request(BASE + path, data=None if payload is None else json.dumps(payload).encode(), headers=headers)
    try:
        response = client.open(req)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.read().decode()

def snapshots(markup):
    return {value['memo']['name']: value for value in [json.loads(html.unescape(raw)) for raw in re.findall(r'wire:snapshot="([^"]+)"', markup)]}

def probes(markup):
    return [json.loads(html.unescape(raw)) for raw in re.findall(r'<pre data-probe>(.*?)</pre>', markup, re.S)]

def initial(project):
    status, markup = request(f'/projects/{project}/edit')
    (ROOT / '.runtime/initial.html').write_text(markup)
    assert status == 200, (status, markup[:1500])
    token = re.search(r'name="csrf-token" content="([^"]+)"', markup).group(1)
    endpoint = re.search(r'data-update-uri="([^"]+)"', markup).group(1)
    if endpoint.startswith(BASE):
        endpoint = endpoint[len(BASE):]
    return snapshots(markup), probes(markup), token, html.unescape(endpoint)

def update(snapshot, token, endpoint, method='tick', params=None, updates=None):
    payload = {'_token': token, 'components': [{'snapshot': json.dumps(snapshot, separators=(',', ':')), 'updates': updates or {}, 'calls': [{'path': '', 'method': method, 'params': params or []}]}]}
    status, body = request(endpoint, payload, token)
    if status != 200:
        try: detail = json.loads(body)
        except json.JSONDecodeError: detail = body[:1000]
        (ROOT / '.runtime/last-error.json').write_text(json.dumps({'status': status, 'detail': detail}, indent=2))
        return status, detail, None
    response = json.loads(body)['components'][0]
    return status, probes(response['effects'].get('html', '')), json.loads(response['snapshot'])

def require(condition, description, details=None):
    assert condition, (description, details)
    results.append({'case': description, 'status': 'passed', 'evidence': details})

versions = subprocess.check_output(['php', '-r', "require 'vendor/autoload.php'; echo json_encode(['php' => PHP_VERSION, 'laravel' => Composer\\InstalledVersions::getPrettyVersion('laravel/framework'), 'livewire' => Composer\\InstalledVersions::getPrettyVersion('livewire/livewire')]);"], cwd=ROOT, text=True)
results = []
snaps, initial_probes, token, endpoint = initial(42)
print(json.dumps({'stage': 'initial', 'endpoint': endpoint, 'probes': initial_probes}, indent=2), flush=True)
parent = next(p for p in initial_probes if p['component'] == 'parent')
child_snapshot = snaps['project-child']
parent_snapshot = snaps['project-page']
require(parent['ambientPage'] == 'projects.edit', 'initial ambient page resolves', parent)
require(parent['explicit']['menu'] == parent['ambientMenus'] and parent['explicit']['actions'] == parent['ambientActions'], 'public explicit menus and actions match ambient output on initial render', parent['explicit'])
require(parent['independentReader']['pageId'] == 'projects.show' and parent['independentReader']['subjectId'] == '77' and parent['originalReaderAfterOther'] == parent['explicit'] and parent['ambientPage'] == 'projects.edit', 'two local readers do not overwrite each other or the ambient request', {'first': parent['explicit'], 'second': parent['independentReader']})
status, values, next_parent = update(parent_snapshot, token, endpoint)
print(json.dumps({'stage': 'update', 'status': status, 'values': values}, indent=2), flush=True)
assert status == 200, values
parent_update = next(p for p in values if p['component'] == 'parent')
require(parent_update['ambientPage'] is None, 'ambient Current loses page identity on update', parent_update)
require(parent_update['ambientActions'] == [] and len(parent_update['ambientMenus']) == 1, 'ambient actions and page-sensitive menu lose their context on update', {'actions': parent_update['ambientActions'], 'menu': parent_update['ambientMenus']})
require(parent_update['explicit']['menu'][2]['current'] is True and parent_update['explicit']['menu'][0]['active'] is True, 'public explicit menu predicates and active states retain page context on update', parent_update['explicit']['menu'])
require([a['id'] for a in parent_update['explicit']['actions']] == ['project.rename', 'project.edit', 'projects.edit.help'] and parent_update['explicit']['actions'][1]['current'] is True and parent_update['explicit']['actions'][2]['label'] == 'Help for Apollo', 'public subject and page actions preserve order context and logical GET current-state', parent_update['explicit']['actions'])
require(parent_update['explicit'] == parent['explicit'], 'explicit local context preserves page and subject on parent update', parent_update['explicit'])
before_child = len((ROOT / '.runtime/events.jsonl').read_text().splitlines())
status, values, next_child = update(child_snapshot, token, endpoint)
assert status == 200, values
events = [json.loads(line) for line in (ROOT / '.runtime/events.jsonl').read_text().splitlines()[before_child:]]
require([event['component'] for event in events] == ['child'], 'child-only update resolves without parent render', events)
require(values[0]['explicit']['subjectId'] == '42', 'child local reader retains subject', values[0]['explicit'])
require(values[0]['explicit']['menu'][2]['current'] is True and values[0]['explicit']['actions'][1]['current'] is True and values[0]['explicit']['actions'][2]['label'] == 'Help for Apollo', 'child-only update resolves public menus page actions and logical current-state', values[0]['explicit'])
connection.execute("UPDATE projects SET name = 'Apollo renamed elsewhere' WHERE id = 42")
connection.commit()
status, values, next_parent = update(next_parent, token, endpoint)
assert status == 200, values
require(values[0]['explicit']['subjectTitle'] == 'Project Apollo renamed elsewhere', 'parent presentation refreshes from server state', values[0]['explicit'])
status, values, next_child = update(next_child, token, endpoint)
assert status == 200, values
require(values[0]['explicit']['subjectTitle'] == 'Project Apollo renamed elsewhere', 'child presentation refreshes independently', values[0]['explicit'])
before_child = len((ROOT / '.runtime/events.jsonl').read_text().splitlines())
status, values, next_child = update(next_child, token, endpoint, 'rename', ['Child renamed project'])
events = [json.loads(line) for line in (ROOT / '.runtime/events.jsonl').read_text().splitlines()[before_child:]]
require(status == 200 and [event['component'] for event in events] == ['child'] and values[0]['explicit']['subjectTitle'] == 'Project Child renamed project', 'successful child mutation refreshes child but does not rerender parent shell', events)
status, values, next_parent = update(next_parent, token, endpoint)
require(status == 200 and values[0]['explicit']['subjectTitle'] == 'Project Child renamed project', 'parent observes child mutation when explicitly refreshed', values)
status, values, next_parent = update(next_parent, token, endpoint, 'rename', ['Apollo renamed locally'])
require(status == 200 and values[0]['explicit']['subjectTitle'] == 'Project Apollo renamed locally', 'successful mutation refreshes presentation within the same update', values)
connection.execute('UPDATE permissions SET allowed = 0 WHERE id = 1')
connection.commit()
status, values, next_parent = update(next_parent, token, endpoint)
assert status == 200, values
require(values[0]['explicit']['actions'][0]['enabled'] is False and values[0]['explicit']['actions'][0]['reason'] == 'Permission revoked.' and values[0]['explicit']['actions'][2]['enabled'] is False and values[0]['explicit']['actions'][2]['reason'] == 'Page help disabled.', 'public explicit subject and page actions recompute availability and reasons', values[0]['explicit']['actions'])
require(values[0]['objectActions'][0]['enabled'] is False and values[0]['objectActions'][0]['reason'] == 'Permission revoked.', 'action availability refreshes after permission revocation', values[0]['objectActions'])
status, detail, _ = update(next_parent, token, endpoint, 'rename', ['Unauthorized parent write'])
require(status == 403, 'parent mutation reauthorizes after permission revocation', status)
status, detail, _ = update(next_child, token, endpoint, 'rename', ['Unauthorized child write'])
require(status == 403, 'child mutation reauthorizes after permission revocation', status)
require(connection.execute('SELECT name FROM projects WHERE id = 42').fetchone()[0] == 'Apollo renamed locally', 'denied mutations do not write')
status, detail, _ = update(next_parent, token, endpoint, updates={'projectId': 77})
require(status != 200 and 'CannotUpdateLockedPropertyException' in json.dumps(detail), 'client cannot change locked subject ID', {'status': status, 'exception': detail.get('exception') if isinstance(detail, dict) else detail})
tampered = copy.deepcopy(next_parent)
tampered['memo']['path'] = 'projects/77/edit'
status, detail, _ = update(tampered, token, endpoint)
require(status != 200 and 'CorruptComponentPayloadException' in json.dumps(detail), 'changed signed route context is rejected', {'status': status, 'exception': detail.get('exception') if isinstance(detail, dict) else detail})
other_snaps, other_probes, token, endpoint = initial(77)
require(other_probes[0]['explicit']['subjectId'] == '77', 'second subject initial render is isolated', other_probes[0]['explicit'])
status, values, _ = update(other_snaps['project-page'], token, endpoint)
require(status == 200 and values[0]['explicit']['subjectId'] == '77', 'second subject update is isolated', values)
status, values, _ = update(next_parent, token, endpoint)
require(status == 200 and values[0]['explicit']['subjectId'] == '42', 'returning to first signed page does not inherit second subject', values)
bundle = {'_token': token, 'components': [{'snapshot': json.dumps(snapshot, separators=(',', ':')), 'updates': {}, 'calls': [{'path': '', 'method': 'tick', 'params': []}]} for snapshot in [next_parent, other_snaps['project-page']]]}
status, body = request(endpoint, bundle, token)
assert status == 200, (status, body[:1000])
bundled = [probes(component['effects']['html'])[0]['explicit']['subjectId'] for component in json.loads(body)['components']]
require(bundled == ['42', '77'], 'two different page contexts remain isolated in one bundled HTTP request', bundled)
connection.execute('UPDATE permissions SET view_allowed = 0 WHERE id = 1')
connection.commit()
status, detail, _ = update(next_parent, token, endpoint)
require(status == 403, 'persistent route authorization rejects revoked page access on parent update', status)
status, detail, _ = update(next_child, token, endpoint)
require(status == 403, 'persistent route authorization rejects revoked page access on child-only update', status)
connection.execute('UPDATE permissions SET view_allowed = 1 WHERE id = 1')
connection.commit()
status, detail, _ = update(next_parent, 'invalid-csrf-token', endpoint)
require(status == 419, 'real HTTP proof retains CSRF enforcement', status)
connection.execute('DELETE FROM projects WHERE id = 42')
connection.commit()
status, detail, _ = update(next_parent, token, endpoint)
require(status == 404, 'deleted subject fails with 404 on parent update', status)
status, detail, _ = update(next_child, token, endpoint)
require(status == 404, 'deleted subject fails with 404 on child update', status)
(ROOT / '.runtime/results.json').write_text(json.dumps({'versions': json.loads(versions), 'results': results, 'count': len(results)}, indent=2)+'\n')
print(json.dumps({'passed': len(results), 'results': str(ROOT / '.runtime/results.json')}, indent=2))
