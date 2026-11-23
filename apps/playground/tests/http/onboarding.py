#!/usr/bin/env python3
"""Actual cookies, CSRF and signed Livewire operations for optional onboarding."""
from pathlib import Path
import html
import http.cookiejar
import json
import re
import sqlite3
import urllib.error
import urllib.parse
import urllib.request

BASE = 'http://127.0.0.1:18765'
RUNTIME = Path(__file__).resolve().parent / '.runtime'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
results = []

def fetch(path, values=None, token=None, json_response=False, browser=client):
    headers = {'Accept': 'application/json' if json_response else 'text/html'}
    data = None if values is None else urllib.parse.urlencode({**values, '_token': token}).encode()
    req = urllib.request.Request(BASE + path, data=data, headers=headers)
    try:
        response = browser.open(req)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.read().decode()

def require(condition, description, detail=None):
    assert condition, (description, detail)
    results.append({'case': description, 'status': 'passed'})

def state():
    status, body = fetch('/onboarding/workspaces/demo?view=json', json_response=True)
    assert status == 200, body[:500]
    return json.loads(body)

def wire_page(path):
    status, markup = fetch(path + '?view=livewire')
    assert status == 200, (path, status, markup[:500])
    snapshot = next(json.loads(html.unescape(raw)) for raw in re.findall(r'wire:snapshot="([^"]+)"', markup))
    token = re.search(r'name="csrf-token" content="([^"]+)"', markup).group(1)
    endpoint = html.unescape(re.search(r'data-update-uri="([^"]+)"', markup).group(1)).removeprefix(BASE)
    return snapshot, token, endpoint

def wire_submit(snapshot, token, endpoint, updates):
    payload = {'_token': token, 'components': [{'snapshot': json.dumps(snapshot, separators=(',', ':')), 'updates': updates, 'calls': [{'path': '', 'method': 'submit', 'params': []}]}]}
    req = urllib.request.Request(BASE + endpoint, data=json.dumps(payload).encode(), headers={'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Livewire': 'true', 'X-CSRF-TOKEN': token})
    try:
        response = client.open(req)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, json.loads(response.read())

connection = sqlite3.connect(RUNTIME / 'playground.sqlite')
before = connection.execute('SELECT id, name FROM projects ORDER BY id').fetchall()
status, markup = fetch('/onboarding?view=blade')
token = re.search(r'name="csrf-token" content="([^"]+)"', markup).group(1)
require(status == 200 and 'Try simulated onboarding' in markup, 'onboarding is optional and renders without an account')
status, body = fetch('/onboarding/workspace', {'workspaceName': 'Skipped'}, token, True)
require(status == 403, 'workspace mutation rejects skipped profile over real HTTP', status)
status, body = fetch('/onboarding/profile', {'name': '', 'email': 'invalid'}, token, True)
require(status == 422 and state()['profile'] is None, 'invalid signup sample does not advance session state', status)
status, body = fetch('/onboarding/profile?view=blade', {'name': 'Alex HTTP', 'email': 'alex@example.test'}, token)
require(status == 200 and 'Prepare a workspace for Alex HTTP' in body and len(state()['steps']) == 2, 'profile submission redirects and reveals the workspace step')
status, body = fetch('/onboarding/workspace?view=blade', {'workspaceName': 'HTTP studio'}, token)
review = state()
require(status == 200 and review['current']['subject']['label'] == 'HTTP studio' and len(review['steps']) == 3, 'workspace submission changes the subject and reveals review')
other = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
status, body = fetch('/onboarding/workspaces/demo?view=json', browser=other, json_response=True)
require(status == 200 and json.loads(body)['profile'] is None and state()['profile']['name'] == 'Alex HTTP', 'simulations remain isolated between cookie sessions')
status, body = fetch('/onboarding/workspaces/demo/finish?view=blade', {}, token)
review = state()
require(status == 200 and review['complete'] and review['current']['actions'][1]['label'] == 'Open Projects', 'finish changes action availability and reveals the Projects action')
status, body = fetch('/onboarding/reset?view=blade', {}, token)
require(status == 200 and state()['profile'] is None and connection.execute('SELECT id, name FROM projects ORDER BY id').fetchall() == before, 'reset clears only simulation state and leaves projects untouched')
fetch('/onboarding/profile', {'name': 'Alex', 'email': 'alex@example.test'}, token)
unsafe = '<script>alert("unsafe")</script>'
fetch('/onboarding/workspace', {'workspaceName': unsafe}, token)
status, body = fetch('/onboarding/workspaces/demo?view=blade')
require(status == 200 and unsafe not in body and '&lt;script&gt;' in body, 'simulated workspace title subject and confirmation render escaped')
fetch('/onboarding/reset', {}, token)

snapshot, token, endpoint = wire_page('/onboarding/profile')
status, response = wire_submit(snapshot, token, endpoint, {'name': '', 'email': 'invalid'})
component = response['components'][0]
require(status == 200 and 'role="alert"' in component['effects']['html'] and state()['profile'] is None, 'Livewire simulated signup shows validation errors without advancing')
snapshot = json.loads(component['snapshot'])
status, response = wire_submit(snapshot, token, endpoint, {'name': 'Wire Alex', 'email': 'wire@example.test'})
require(status == 200 and '/onboarding/workspace?view=livewire' in response['components'][0]['effects']['redirect'], 'Livewire signup redirects through an explicit component operation')
snapshot, token, endpoint = wire_page('/onboarding/workspace')
status, response = wire_submit(snapshot, token, endpoint, {'workspaceName': 'Wire studio'})
require(status == 200 and state()['current']['title'] == 'Review Wire studio', 'Livewire workspace operation refreshes Current presentation from session state')
snapshot, token, endpoint = wire_page('/onboarding/workspaces/demo')
status, response = wire_submit(snapshot, token, endpoint, {})
require(status == 200 and state()['complete'], 'Livewire finishes through the same server-side policy')
status, response = wire_submit(snapshot, token, endpoint, {'pageId': 'onboarding.profile'})
require(status != 200 and 'CannotUpdateLockedPropertyException' in json.dumps(response), 'Livewire client cannot change the logical onboarding page')
fetch('/onboarding/reset', {}, token)
status, response = wire_submit(snapshot, token, endpoint, {})
require(status == 403 and not state()['complete'], 'stale Livewire finish snapshot rechecks reset simulation state')

(RUNTIME / 'onboarding.json').write_text(json.dumps({'results': results, 'count': len(results)}, indent=2) + '\n')
print(json.dumps({'passed': len(results), 'example': 'onboarding'}))
