#!/usr/bin/env python3
"""Maintained HTTP/signed-snapshot lifecycle and guide rendering checks."""
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
import urllib.parse

ROOT = Path(__file__).resolve().parents[2]
RUNTIME = Path(__file__).resolve().parent / '.runtime'
BASE = 'http://127.0.0.1:18765'
DB = RUNTIME / 'playground.sqlite'
DB.parent.mkdir(parents=True, exist_ok=True)
connection = sqlite3.connect(DB)
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
    return {('project-child' if value['data']['nested'] else 'project-page'): value for value in [json.loads(html.unescape(raw)) for raw in re.findall(r'wire:snapshot="([^"]+)"', markup)]}

def probes(markup):
    return [json.loads(html.unescape(raw)) for raw in re.findall(r'<pre data-probe>(.*?)</pre>', markup, re.S)]

def initial(project):
    status, markup = request(f'/projects/{project}/edit?view=livewire')
    (RUNTIME / 'initial.html').write_text(markup)
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
        (RUNTIME / 'last-error.json').write_text(json.dumps({'status': status, 'detail': detail}, indent=2))
        return status, detail, None
    response = json.loads(body)['components'][0]
    (RUNTIME / 'last-component.html').write_text(response['effects'].get('html', ''))
    return status, probes(response['effects'].get('html', '')), json.loads(response['snapshot'])

def require(condition, description, details=None):
    assert condition, (description, details)
    results.append({'case': description, 'status': 'passed', 'evidence': details})

versions = subprocess.check_output(['php', '-r', "require 'vendor/autoload.php'; echo json_encode(['php' => PHP_VERSION, 'laravel' => Composer\\InstalledVersions::getPrettyVersion('laravel/framework'), 'livewire' => Composer\\InstalledVersions::getPrettyVersion('livewire/livewire')]);"], cwd=ROOT, text=True)
results = []
# Exercise each new Livewire page through its real update endpoint, not only GET.
for path, expected in [('/projects', 'projects'), ('/projects/42', 'projects.show'),
                       ('/projects/42/tasks', 'projects.tasks'), ('/projects/42/tasks/1', 'tasks.show'),
                       ('/projects/42/tasks/create', 'tasks.create'), ('/projects/42/tasks/1/edit', 'tasks.edit')]:
    status, markup = request(path + '?view=livewire')
    assert status == 200, (path, status, markup[:500])
    snapshot = snapshots(markup)['project-page']
    page_token = re.search(r'name="csrf-token" content="([^"]+)"', markup).group(1)
    page_endpoint = html.unescape(re.search(r'data-update-uri="([^"]+)"', markup).group(1))
    if page_endpoint.startswith(BASE):
        page_endpoint = page_endpoint[len(BASE):]
    status, values, _ = update(snapshot, page_token, page_endpoint)
    require(status == 200 and values[0]['explicit']['pageId'] == expected,
            'Livewire retains page identity after update: ' + expected, status)

snaps, initial_probes, token, endpoint = initial(42)
parent = next(p for p in initial_probes if p['component'] == 'parent')
child_snapshot = snaps['project-child']
parent_snapshot = snaps['project-page']
require(parent['ambientPage'] == 'projects.edit', 'initial ambient page resolves', parent)
require(parent['explicit']['menu'] == parent['ambientMenus'] and parent['explicit']['actions'] == parent['ambientActions'], 'public explicit menus and actions match ambient output on initial render', parent['explicit'])
first = parent['explicit']
other = json.loads(client.open(BASE + '/api/projects/77/experience').read())
require(other['subjectId'] == '77' and first['subjectId'] == '42', 'JSON reader does not inherit another project context', other)
status, values, next_parent = update(parent_snapshot, token, endpoint)
assert status == 200, values
parent_update = next(p for p in values if p['component'] == 'parent')
require(parent_update['ambientPage'] is None, 'ambient Current loses page identity on update', parent_update)
require(parent_update['ambientActions'] == [] and not any(item['active'] for item in parent_update['ambientMenus']), 'ambient actions and page-sensitive menu lose their context on update', {'actions': parent_update['ambientActions'], 'menu': parent_update['ambientMenus']})
require(any(item['pageId'] == 'projects' and item['active'] for item in parent_update['explicit']['menu']), 'explicit menu retains active project context', parent_update['explicit']['menu'])
require([a['id'] for a in parent_update['explicit']['actions']] == ['project.rename', 'project.edit'] and parent_update['explicit']['actions'][1]['current'] is True, 'visible subject actions preserve order and logical GET current-state', parent_update['explicit']['actions'])
require(parent_update['explicit'] == parent['explicit'], 'explicit local context preserves page and subject on parent update', parent_update['explicit'])
status, values, next_child = update(child_snapshot, token, endpoint)
assert status == 200, values
require(len(values) == 1 and values[0]['component'] == 'child', 'child-only update renders only child', values)
require(values[0]['explicit']['subjectId'] == '42', 'child local reader retains subject', values[0]['explicit'])
require(any(item['pageId'] == 'projects' and item['active'] for item in values[0]['explicit']['menu']), 'explicit menu retains active project context', values[0]['explicit']['menu'])
connection.execute("UPDATE projects SET name = 'Apollo renamed elsewhere' WHERE id = 42")
connection.commit()
status, values, next_parent = update(next_parent, token, endpoint)
assert status == 200, values
require(values[0]['explicit']['subjectTitle'] == 'Project Apollo renamed elsewhere', 'parent presentation refreshes from server state', values[0]['explicit'])
status, values, next_child = update(next_child, token, endpoint)
assert status == 200, values
require(values[0]['explicit']['subjectTitle'] == 'Project Apollo renamed elsewhere', 'child presentation refreshes independently', values[0]['explicit'])
status, values, next_child = update(next_child, token, endpoint, 'rename', ['Child renamed project'])
require(status == 200 and len(values) == 1 and values[0]['component'] == 'child' and values[0]['explicit']['subjectTitle'] == 'Project Child renamed project', 'successful child mutation refreshes only child', values)
status, values, next_parent = update(next_parent, token, endpoint)
require(status == 200 and values[0]['explicit']['subjectTitle'] == 'Project Child renamed project', 'parent observes child mutation when explicitly refreshed', values)
status, values, next_parent = update(next_parent, token, endpoint, 'rename', ['Apollo renamed locally'])
require(status == 200 and values[0]['explicit']['subjectTitle'] == 'Project Apollo renamed locally', 'successful mutation refreshes presentation within the same update', values)
connection.execute('UPDATE projects SET is_editable = 0')
connection.commit()
status, values, next_parent = update(next_parent, token, endpoint)
assert status == 200, values
require(values[0]['explicit']['actions'][0]['enabled'] is False and values[0]['explicit']['actions'][0]['reason'] == 'This project is read-only.', 'actions recompute availability and reasons', values[0]['explicit']['actions'])
require(values[0]['objectActions'][0]['enabled'] is False and values[0]['objectActions'][0]['reason'] == 'This project is read-only.', 'action availability refreshes after permission revocation', values[0]['objectActions'])
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
connection.execute('UPDATE projects SET is_visible = 0')
connection.commit()
status, detail, _ = update(next_parent, token, endpoint)
require(status == 403, 'persistent route authorization rejects revoked page access on parent update', status)
status, detail, _ = update(next_child, token, endpoint)
require(status == 403, 'persistent route authorization rejects revoked page access on child-only update', status)
connection.execute('UPDATE projects SET is_visible = 1')
connection.commit()
status, detail, _ = update(next_parent, 'invalid-csrf-token', endpoint)
require(status == 419, 'real HTTP proof retains CSRF enforcement', status)
# Exercise the exact Blade snippet extracted from the guide and native form route.
connection.execute('UPDATE projects SET is_editable = 1')
connection.commit()
def form(name, accept='application/json', referer=None):
    payload = urllib.parse.urlencode({'_token': token, 'name': name}).encode()
    req = urllib.request.Request(BASE + '/projects/42/rename', data=payload, headers={'Accept': accept, 'Content-Type': 'application/x-www-form-urlencoded', **({'Referer': BASE + referer} if referer else {})})
    try:
        response = client.open(req)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.read().decode()
before_invalid = connection.execute('SELECT name FROM projects WHERE id = 42').fetchone()[0]
status, body = form('')
require(status == 422 and connection.execute('SELECT name FROM projects WHERE id = 42').fetchone()[0] == before_invalid, 'native form validates input before writing', status)
status, body = form('Native form name')
require(status == 200 and json.loads(body)['name'] == 'Native form name', 'native form executes its declared route with CSRF and authorization', status)
for destination in ['/projects/42/edit?view=livewire', '/projects/42/edit?view=blade']:
    status, body = form('Browser form name', 'text/html', destination)
    assert status == 200 and 'Project renamed.' in body and '<h2>Browser form name</h2>' in body, (destination, status, body[:1000])
status, body = form('', 'text/html', '/projects/42/edit?view=livewire')
assert status == 200 and 'role="alert"' in body and '<h2>Browser form name</h2>' in body, (status, body[:1000])
status, body = form('Native form name')
assert status == 200
status, directory = request('/projects?view=blade')
assert status == 200 and 'Explore project' in directory and '/projects/42' in directory, (status, directory[:1000])
status, detail = request('/projects/42?view=blade')
assert status == 200 and '<h1>Project Native form name</h1>' in detail and '/projects/42/edit' in detail, (status, detail[:1000])

status, markup = request('/projects/42/edit?view=blade')
require(status == 200 and 'Edit Native form name' in markup and 'Native form name' in markup and 'method="post"' in markup, 'Blade renders page overrides subject navigation and route forms', status)
unsafe = '<script>alert("unsafe")</script>'
connection.execute('UPDATE projects SET name = ? WHERE id = 42', (unsafe,))
connection.commit()
status, markup = request('/projects/42/edit?view=blade')
require(status == 200 and unsafe not in markup and '&lt;script&gt;' in markup and 'Rename &lt;script&gt;' in markup, 'Blade escapes subject labels descriptions and confirmation text', status)
status, values, _ = update(next_child, token, endpoint)
require(status == 200 and values[0]['explicit']['subjectTitle'] == 'Project ' + unsafe and unsafe not in (RUNTIME / 'last-component.html').read_text() and '&lt;script&gt;' in (RUNTIME / 'last-component.html').read_text(), 'Livewire preserves escaped rendering and fresh subject data', values)
connection.execute('UPDATE projects SET is_editable = 0')
connection.commit()
status, markup = request('/projects/42/edit?view=blade')
require(status == 200 and 'disabled' in markup and 'This project is read-only.' in markup, 'Blade renders disabled actions and reasons', status)
status, body = form('Denied native form')
require(status == 403 and connection.execute('SELECT name FROM projects WHERE id = 42').fetchone()[0] == unsafe, 'native form route reauthorizes rather than trusting disabled presentation', status)
connection.execute('DELETE FROM projects WHERE id = 42')
connection.commit()
status, detail, _ = update(next_parent, token, endpoint)
require(status == 404, 'deleted subject fails with 404 on parent update', status)
status, detail, _ = update(next_child, token, endpoint)
require(status == 404, 'deleted subject fails with 404 on child update', status)
(RUNTIME / 'projects.json').write_text(json.dumps({'versions': json.loads(versions), 'results': results, 'count': len(results)}, indent=2)+'\n')
print(json.dumps({'passed': len(results), 'results': str(RUNTIME / 'projects.json')}, indent=2))
