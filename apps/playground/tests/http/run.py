#!/usr/bin/env python3
"""Exercise the real playground with an isolated, disposable SQLite database."""
from pathlib import Path
import os
import json
import sys
import signal
import socket
import subprocess
import time

root = Path(__file__).resolve().parents[2]
runtime = Path(__file__).resolve().parent / '.runtime'
runtime.mkdir(exist_ok=True)
if not (root / 'vendor/autoload.php').exists():
    raise SystemExit('Run composer install in apps/playground first.')
with socket.socket() as port:
    port.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    port.bind(('127.0.0.1', 18765))
database = runtime / 'playground.sqlite'
database.unlink(missing_ok=True)
database.touch()
env = {**os.environ, 'APP_ENV': 'local', 'APP_DEBUG': 'true',
       'APP_KEY': 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=',
       'APP_URL': 'http://127.0.0.1:18765', 'DB_CONNECTION': 'sqlite',
       'DB_DATABASE': str(database), 'DB_URL': '', 'CACHE_STORE': 'array',
       'SESSION_DRIVER': 'file', 'SESSION_COOKIE': 'current_http_review',
       'SESSION_SECURE_COOKIE': 'false', 'QUEUE_CONNECTION': 'sync',
       'MAIL_MAILER': 'array', 'APP_CONFIG_CACHE': str(runtime / 'config.php')}
subprocess.run(['php', 'artisan', 'migrate', '--force', '--no-interaction'], cwd=root, env=env, check=True)
with (runtime / 'server.log').open('w') as log:
    server = subprocess.Popen(['php', '-S', '127.0.0.1:18765', '-t', str(root / 'public'), str(root / 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], cwd=root / 'public', env=env, stdout=log, stderr=log)
    try:
        for _ in range(100):
            if server.poll() is not None:
                raise RuntimeError('Playground server exited; see tests/http/.runtime/server.log')
            try:
                with socket.create_connection(('127.0.0.1', 18765), timeout=0.1):
                    break
            except OSError:
                time.sleep(0.05)
        else:
            raise RuntimeError('Playground server did not start')
        if '--serve' in sys.argv:
            signal.signal(signal.SIGTERM, lambda *_: sys.exit(0))
            signal.signal(signal.SIGINT, lambda *_: sys.exit(0))
            while server.poll() is None:
                time.sleep(0.2)
        else:
            reports = []
            for example in ['projects', 'onboarding']:
                subprocess.run(['python3', str(Path(__file__).with_name(example + '.py'))], cwd=root, env=env, check=True)
                reports.append(json.loads((runtime / (example + '.json')).read_text()))
            results = [case for report in reports for case in report['results']]
            (runtime / 'results.json').write_text(json.dumps({'versions': reports[0]['versions'], 'results': results, 'count': len(results)}, indent=2) + '\n')
            print(json.dumps({'passed': len(results), 'examples': len(reports)}))
    finally:
        server.terminate()
        server.wait(timeout=5)
