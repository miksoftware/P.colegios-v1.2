#!/bin/bash
cd "c:/laragon/www/P.colegios-v1.2.worktrees/agents-remove-create-contract-option"

echo "===== GIT LOG (last 5 commits) ====="
git log --no-pager --oneline -5

echo ""
echo "===== GIT STATUS ====="
git status --short

echo ""
echo "===== GIT DIFF --CACHED --STAT ====="
git diff --cached --stat

echo ""
echo "===== GIT DIFF --STAT ====="
git diff --stat

echo ""
echo "===== STAGING ALL CHANGES ====="
git add -A

echo ""
echo "===== GIT STATUS AFTER STAGING ====="
git status --short

echo ""
echo "===== COMMITTING ====="
git commit -m "feat(precontractual): Restrict 'Create Contract' button to only show when no contract exists yet

- Added eager-load of 'contract' relationship in PrecontractualManagement viewDetail method
- Wrapped 'Crear Contrato' button with @if(!$convocatoria->contract) condition in blade template
- This prevents users from attempting to create multiple contracts for the same convocatoria

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"

echo ""
echo "===== GET COMMIT HASH ====="
git log --no-pager --oneline -1

echo ""
echo "===== PUSHING TO ORIGIN ====="
git push origin agents/remove-create-contract-option

echo ""
echo "===== PUSH COMPLETE ====="
