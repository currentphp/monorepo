#!/usr/bin/env python3
"""Run the throwaway HTTP proof and always stop the server started here."""
from pathlib import Path
import socket
import subprocess
import time

root = Path(__file__).resolve().parent
for directory in ['bootstrap/cache', '.runtime/storage/framework/views', '.runtime/storage/framework/sessions', '.runtime/storage/logs']:
    (root / directory).mkdir(parents=True, exist_ok=True)
if not (root / 'vendor/autoload.php').exists():
    subprocess.run(['composer', 'install', '--no-interaction', '--prefer-dist'], cwd=root, check=True)
# Refuse to use an unrelated service if the fixed prototype port is occupied.
with socket.socket() as port:
    port.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    port.bind(('127.0.0.1', 18765))
with (root / '.runtime/server.log').open('w') as log:
    server = subprocess.Popen(['php', '-S', '127.0.0.1:18765', 'server.php'], cwd=root, stdout=log, stderr=log)
    try:
        for _ in range(100):
            if server.poll() is not None:
                raise RuntimeError('Prototype server exited; see .runtime/server.log')
            try:
                with socket.create_connection(('127.0.0.1', 18765), timeout=0.1):
                    break
            except OSError:
                time.sleep(0.05)
        else:
            raise RuntimeError('Prototype server did not start')
        subprocess.run(['python3', 'scenarios.py'], cwd=root, check=True)
    finally:
        server.terminate()
        server.wait(timeout=5)
