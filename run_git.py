#!/usr/bin/env python3
import subprocess
import sys
import os

os.chdir('c:\\laragon\\www\\P.colegios-v1.2.worktrees\\agents-remove-create-contract-option')

commands = [
    ['git', 'log', '--no-pager', '--oneline', '-10'],
    ['git', 'status', '--short'],
    ['git', 'diff', '--cached', '--stat'],
    ['git', 'diff', '--stat'],
]

for cmd in commands:
    print(f"\n{'='*60}")
    print(f"Running: {' '.join(cmd)}")
    print(f"{'='*60}")
    result = subprocess.run(cmd, capture_output=False)
    if result.returncode != 0:
        print(f"Command failed with return code {result.returncode}")
