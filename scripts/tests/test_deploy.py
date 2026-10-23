"""Offline tests. All gh calls, approval input and state writes use mocks/temp files."""
import contextlib
import importlib.util
import io
import json
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('deploy', Path(__file__).resolve().parents[1] / 'deploy.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)
SHA = 'a' * 40


class Terminal(io.StringIO):
    def isatty(self): return True


class FakeGH:
    def __init__(self):
        self.calls = []
        self.dispatched = None
        self.failure = False
        self.no_history = False
        self.bad_log = False
        self.bad_identity = False
        self.ambiguous = False
        self.auth_failure = False
        self.fail_upload = False
        self.dispatch_error = False

    def run(self, id=10, mode='dry-run'):
        return dict(databaseId=id, displayTitle='Theme ' + mode + ' by @tester',
                    headSha=SHA, headBranch='main', event='workflow_dispatch', status='completed',
                    conclusion='failure' if self.failure else 'success')

    def __call__(self, *args):
        self.calls.append(args)
        if args[:2] == ('auth', 'status'):
            if self.auth_failure: raise m.Stop('auth unavailable')
            return ''
        if args[0] == 'api':
            if args[1] == 'user': return json.dumps({'login': 'tester'})
            if 'git/ref' in args[1]: return json.dumps({'object': {'sha': SHA}})
            return json.dumps({'path': 'wrong.yml' if self.bad_identity else m.WORKFLOW_PATH, 'head_sha': SHA})
        if args[:2] == ('run', 'list'):
            history = [] if self.no_history else [self.run()]
            if self.dispatched:
                history += [self.run(11, self.dispatched)]
                if self.ambiguous: history += [self.run(12, self.dispatched)]
            return json.dumps(history)
        if args[:2] == ('workflow', 'run'):
            self.dispatched = args[args.index('-f')+1].split('=')[1]
            if self.dispatched == 'upload' and self.fail_upload: self.failure = True
            if self.dispatch_error: raise m.Stop('Dispatch response lost; inspect GitHub')
            return ''
        if args[:2] == ('run', 'view'):
            if '--log' in args:
                if self.bad_log: return ''
                lines = ['Mode: dry-run', 'Commit: '+SHA, 'Fixed destination: '+m.DEST,
                         'PLAN OVERWRITE functions.php 10 bytes', 'Planned files: 1 (all local theme files; not a content diff)',
                         'DRY RUN complete: no STOR, MKD, DELE, RMD or rename operations performed.']
                return '\n'.join('deploy\tPlan\t2026-10-23T00:00:00.0000000Z '+x for x in lines)
            return json.dumps(self.run(int(args[2]), self.dispatched if args[2]=='11' else 'dry-run'))
        raise AssertionError(args)


class DeployTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.cache = Path(self.tmp.name) / 'state.json'
        self.fake = FakeGH()

    def tearDown(self): self.tmp.cleanup()

    def invoke(self, mode, approval=None, tty=True, local_dirty=False, head_values=None, installed=True):
        output = Terminal()
        stdin = Terminal() if tty else io.StringIO()
        def git(args):
            self.assertEqual(args[0], 'git')
            return ' M file' if '--porcelain' in args and local_dirty else ('' if '--porcelain' in args else SHA)
        with patch.object(m, 'gh', self.fake), patch.object(m, 'command', git), \
             patch.object(m, 'state_path', return_value=self.cache), \
             patch.object(m.shutil, 'which', return_value='/mock/gh' if installed else None), \
             patch.object(m.sys, 'argv', ['deploy.py', mode]), patch.object(m.sys, 'stdin', stdin), \
             patch.object(m.sys, 'stdout', output), patch('builtins.input', return_value=approval), \
             patch.object(m.time, 'sleep'), contextlib.redirect_stderr(io.StringIO()):
            if head_values:
                with patch.object(m, 'head', side_effect=head_values): m.main()
            else: m.main()
        return output.getvalue()

    def uploads(self): return [c for c in self.fake.calls if c[:2]==('workflow','run') and 'mode=upload' in c]

    def test_dry_run_saves_verified_sha(self):
        self.invoke('dry-run')
        self.assertEqual(json.loads(self.cache.read_text())['sha'], SHA)
        self.assertFalse(self.uploads())

    def test_dry_run_failure_has_no_cache(self):
        self.fake.failure=True
        with self.assertRaises(m.Stop): self.invoke('dry-run')
        self.assertFalse(self.cache.exists())

    def test_dry_run_head_changed_has_no_cache(self):
        with self.assertRaises(m.Stop): self.invoke('dry-run', head_values=[SHA,SHA,'b'*40])
        self.assertFalse(self.cache.exists())

    def test_deploy_requires_tty(self):
        with self.assertRaises(m.Stop): self.invoke('deploy', tty=False)
        self.assertFalse(self.uploads())

    def test_deploy_rejects_no_history(self):
        self.fake.no_history=True
        with self.assertRaises(m.Stop): self.invoke('deploy')
        self.assertFalse(self.uploads())

    def test_deploy_rejects_stale_cache(self):
        self.cache.write_text(json.dumps({'repository':m.REPO,'sha':'b'*40,'run_id':10}))
        with self.assertRaises(m.Stop): self.invoke('deploy')
        self.assertFalse(self.uploads())

    def test_deploy_rejects_missing_log_evidence(self):
        self.fake.bad_log=True
        with self.assertRaises(m.Stop): self.invoke('deploy')
        self.assertFalse(self.uploads())

    def test_deploy_rejects_wrong_workflow(self):
        self.fake.bad_identity=True
        with self.assertRaises(m.Stop): self.invoke('deploy')
        self.assertFalse(self.uploads())

    def test_deploy_rejects_unapproved(self):
        with self.assertRaises(m.Stop): self.invoke('deploy', approval='yes')
        self.assertFalse(self.uploads())

    def test_deploy_rejects_dirty_worktree(self):
        with self.assertRaises(m.Stop): self.invoke('deploy', local_dirty=True)
        self.assertFalse(self.uploads())

    def test_main_change_during_approval(self):
        with self.assertRaises(m.Stop): self.invoke('deploy', approval='DEPLOY '+SHA, head_values=[SHA,SHA,'b'*40])
        self.assertFalse(self.uploads())

    def test_explicit_mock_approval_dispatches_once_with_correct_fields(self):
        self.invoke('deploy', approval='DEPLOY '+SHA)
        calls=self.uploads(); self.assertEqual(len(calls),1)
        self.assertIn('reviewed_sha='+SHA,calls[0]); self.assertIn('confirmation=DEPLOY TUOUGM5pjax',calls[0])

    def test_failed_upload_not_retried(self):
        self.fake.fail_upload=True
        with self.assertRaises(m.Stop): self.invoke('deploy', approval='DEPLOY '+SHA)
        self.assertEqual(len(self.uploads()),1)

    def test_ambiguous_dispatch_not_retried(self):
        self.fake.ambiguous=True
        with self.assertRaises(m.Stop): self.invoke('dry-run')
        self.assertEqual(sum(c[:2]==('workflow','run') for c in self.fake.calls),1)

    def test_dispatch_network_error_not_retried(self):
        self.fake.dispatch_error=True
        with self.assertRaises(m.Stop): self.invoke('deploy', approval='DEPLOY '+SHA)
        self.assertEqual(len(self.uploads()),1)

    def test_cli_error_does_not_disclose_stderr(self):
        from types import SimpleNamespace
        with patch.object(m.subprocess,'run',return_value=SimpleNamespace(returncode=1,stdout='',stderr='mock-secret-value')):
            with self.assertRaises(m.Stop) as error: m.command(['gh','auth','status'])
        self.assertNotIn('mock-secret-value',str(error.exception))

    def test_missing_gh(self):
        with self.assertRaises(m.Stop): self.invoke('dry-run', installed=False)
        self.assertFalse(self.fake.calls)

    def test_missing_auth(self):
        self.fake.auth_failure=True
        with self.assertRaises(m.Stop): self.invoke('dry-run')
        self.assertEqual(len(self.fake.calls),1)


if __name__ == '__main__': unittest.main()
