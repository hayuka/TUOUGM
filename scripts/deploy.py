#!/usr/bin/env python3
"""Dispatch the existing fixed FTPS workflow; never access FTPS credentials."""
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import time

REPO = 'hayuka/TUOUGM'
WORKFLOW = 'deploy-theme-ftps.yml'
WORKFLOW_PATH = '.github/workflows/' + WORKFLOW
ROOT = Path(__file__).resolve().parent.parent
DEST = '/tuougm/wp-content/themes/TUOUGM5pjax/'
FIELDS = 'databaseId,displayTitle,headSha,headBranch,event,status,conclusion,url'


class Stop(Exception):
    pass


def command(args):
    env = dict(os.environ, GH_HOST='github.com', GH_PROMPT_DISABLED='1', GH_PAGER='cat')
    env.pop('GH_DEBUG', None)
    result = subprocess.run(args, cwd=ROOT, env=env, text=True, capture_output=True)
    if result.returncode:
        raise Stop('Command failed: ' + ' '.join(args[:2]) + '. No automatic retry; inspect the run in GitHub.')
    return result.stdout


def gh(*args):
    return command(['gh', *args])


def api(path):
    return json.loads(gh('api', path))


def head():
    sha = api('repos/' + REPO + '/git/ref/heads/main')['object']['sha']
    if not re.fullmatch('[0-9a-f]{40}', sha):
        raise Stop('Invalid main SHA')
    return sha


def runs():
    return json.loads(gh('run', 'list', '--repo', REPO, '--workflow', WORKFLOW,
                         '--branch', 'main', '--event', 'workflow_dispatch', '--limit', '100', '--json', FIELDS))


def view(run_id):
    return json.loads(gh('run', 'view', str(run_id), '--repo', REPO, '--json', FIELDS))


def matches(run, mode, sha):
    return (run.get('headSha') == sha and run.get('headBranch') == 'main'
            and run.get('event') == 'workflow_dispatch'
            and re.fullmatch(r'Theme ' + re.escape(mode) + r' by @[A-Za-z0-9-]+', run.get('displayTitle', '')))


def payloads(log):
    # gh --log prefixes each line with job, step, timestamp. Ignore echoed script.
    return [m.group(1) for line in log.splitlines()
            for m in [re.search(r'\b\d{4}-\d\d-\d\dT\S+Z (.*)$', line)] if m]


def review_plan(run_id, sha):
    run = view(run_id)
    if not matches(run, 'dry-run', sha) or run['status'] != 'completed' or run['conclusion'] != 'success':
        raise Stop('Dry Run is not a successful manual main run for the reviewed SHA')
    # Verify workflow identity independently of display title.
    detail = api('repos/' + REPO + '/actions/runs/' + str(run_id))
    if detail.get('path') != WORKFLOW_PATH or detail.get('head_sha') != sha:
        raise Stop('Dry Run workflow identity mismatch')
    lines = payloads(gh('run', 'view', str(run_id), '--repo', REPO, '--log'))
    required = ['Mode: dry-run', 'Commit: ' + sha, 'Fixed destination: ' + DEST,
                'DRY RUN complete: no STOR, MKD, DELE, RMD or rename operations performed.']
    if not all(item in lines for item in required):
        raise Stop('Dry Run evidence/logs missing; run a new Dry Run')
    plans = [line for line in lines if line.startswith(('PLAN ', 'Planned files:'))]
    if not any(line.startswith('Planned files:') for line in plans):
        raise Stop('Dry Run plan missing')
    print('Reviewed Dry Run: https://github.com/' + REPO + '/actions/runs/' + str(run_id))
    for line in plans:
        print(json.dumps(line, ensure_ascii=False))  # Escape terminal control characters.
    return run


def wait(run_id, mode, sha):
    print('Run: https://github.com/' + REPO + '/actions/runs/' + str(run_id), flush=True)
    print('Waiting (Environment approval, if configured, must be done by its reviewer).', flush=True)
    deadline = time.monotonic() + 7200
    while time.monotonic() < deadline:
        run = view(run_id)
        if not matches(run, mode, sha):
            raise Stop('Run identity/SHA changed; inspect GitHub. No redispatch performed.')
        if run['status'] == 'completed':
            print('Result:', run['conclusion'], flush=True)
            if run['conclusion'] != 'success':
                raise Stop('Workflow failed or was cancelled. No automatic retry.')
            return
        time.sleep(10)
    raise Stop('Wait timed out. The run may still be active; inspect GitHub before another command.')


def dispatch(mode, sha):
    actor = api('user')['login']
    known = {run['databaseId'] for run in runs()}
    if head() != sha:
        raise Stop('main changed before dispatch; repeat Dry Run')
    fields = ['-f', 'mode=' + mode]
    if mode == 'upload':
        fields += ['-f', 'reviewed_sha=' + sha, '-f', 'confirmation=DEPLOY TUOUGM5pjax']
    # Once only: network failure can occur after acceptance; never auto-redispatch.
    gh('workflow', 'run', WORKFLOW, '--repo', REPO, '--ref', 'main', *fields)
    print('Dispatch accepted; locating the run. Do not start another deployment.', flush=True)
    deadline = time.monotonic() + 120
    while time.monotonic() < deadline:
        new = [run for run in runs() if run['databaseId'] not in known
               and run.get('displayTitle') == 'Theme ' + mode + ' by @' + actor]
        if len(new) > 1:
            raise Stop('Ambiguous simultaneous runs. Inspect GitHub; no automatic retry.')
        if new:
            run = new[0]
            if not matches(run, mode, sha):
                raise Stop('main changed at dispatch. Workflow SHA guard prevents upload; inspect GitHub.')
            wait(run['databaseId'], mode, sha)
            return run['databaseId']
        time.sleep(3)
    raise Stop('Could not identify dispatched run; it may exist. Inspect GitHub; do not blindly retry.')


def state_path():
    path = Path(command(['git', 'rev-parse', '--git-path', 'tuougm-deploy/last-dry-run.json']).strip())
    return path if path.is_absolute() else ROOT / path


def save_state(path, sha, run_id):
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_suffix('.tmp')
    temporary.write_text(json.dumps({'repository': REPO, 'sha': sha, 'run_id': run_id}) + '\n')
    temporary.replace(path)


def main():
    if len(sys.argv) != 2 or sys.argv[1] not in {'dry-run', 'deploy'}:
        raise Stop('Usage: ./scripts/deploy.sh dry-run|deploy')
    mode = sys.argv[1]
    if not shutil.which('gh'):
        raise Stop('GitHub CLI is not installed. See docs/deployment-cli.md (brew install gh; gh auth login).')
    try:
        gh('auth', 'status', '--hostname', 'github.com')
    except Stop:
        raise Stop('GitHub authentication unavailable. Run gh auth login --hostname github.com and gh auth status. See docs/deployment-cli.md.')
    # No token output, Secrets access, git commit/push or approval APIs.
    if mode == 'deploy' and (not sys.stdin.isatty() or not sys.stdout.isatty()):
        raise Stop('Deploy requires an interactive human terminal. No approval bypass is available.')
    sha = head()
    cache = state_path()
    if mode == 'dry-run':
        run_id = dispatch('dry-run', sha)
        review_plan(run_id, sha)
        if head() != sha:
            raise Stop('main changed during Dry Run. No reusable approval recorded; repeat Dry Run.')
        save_state(cache, sha, run_id)
        print('Dry Run succeeded for SHA:', sha)
        return

    if command(['git', 'status', '--porcelain']).strip() or command(['git', 'rev-parse', 'HEAD']).strip() != sha:
        raise Stop('Local worktree must be clean and HEAD must equal remote main. Commit/push is never automatic.')
    if cache.exists():
        state = json.loads(cache.read_text())
        if state.get('repository') != REPO or state.get('sha') != sha:
            raise Stop('main differs from the recorded Dry Run SHA; run dry-run again.')
        run_id = state['run_id']
    else:
        candidates = [r for r in runs() if matches(r, 'dry-run', sha)
                      and r['status'] == 'completed' and r['conclusion'] == 'success']
        if not candidates:
            raise Stop('No successful Dry Run for current main. Run dry-run first.')
        run_id = candidates[0]['databaseId']
    review_plan(run_id, sha)  # Cache is not proof; always revalidate GitHub history/logs.
    if head() != sha:
        raise Stop('main changed while checking the plan; repeat Dry Run.')
    print('Repository:', REPO, '\nCommit:', sha, '\nSource: themes/TUOUGM5pjax/\nDestination:', DEST)
    print('All planned theme files may be overwritten; no remote deletion. Confirm backup is complete.')
    expected = 'DEPLOY ' + sha
    print('Human approval required. Type exactly:', expected, flush=True)
    answer = input('> ')
    if answer != expected:
        raise Stop('Not approved. No upload dispatched.')
    # Recheck both history and main after an arbitrarily long human pause.
    review_plan(run_id, sha)
    if head() != sha:
        raise Stop('main changed after approval; repeat Dry Run.')
    dispatch('upload', sha)


if __name__ == '__main__':
    try:
        main()
    except (Stop, OSError, ValueError, KeyError, EOFError) as error:
        # Only our Stop messages are safe to print; never dump gh stderr or auth data.
        print(str(error) if isinstance(error, Stop) else 'Deployment stopped: invalid state or unavailable data. No automatic retry.', file=sys.stderr)
        sys.exit(1)
    except KeyboardInterrupt:
        print('Stopped locally. A dispatched run may still be running; inspect GitHub. No automatic retry.', file=sys.stderr)
        sys.exit(130)
