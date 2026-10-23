# ターミナルからのTUOUGMデプロイ

2026-10-23。実装・オフラインテスト完了、レビュー待ち。今回commit、push、GitHub Actions起動、本番接続・アップロードは実施していない。
既存FTPS処理・対象・Secrets・バックアップ手順は [deployment-ftps.md](deployment-ftps.md) を参照。ワークフローは変更していない。

## 必要な環境

Bash、Python 3（標準ライブラリのみ）、Git、GitHub CLI `gh` が必要。
この作業端末ではghが見つからなかったため、実アカウントのauth statusは未確認。自動インストール・認証はしていない。

macOSでHomebrewを導入済みの場合:

```sh
brew install gh
gh auth login --hostname github.com
gh auth status --hostname github.com
```

Homebrew未導入・他OSの場合は [GitHub CLI公式インストール案内](https://github.com/cli/cli#installation) を参照する。
認証には対象リポジトリの参照とActionsの実行権限が必要。fine-grained PATを使う場合は対象repoのActions read/writeとContents read、本人の利用条件に応じた権限を確認する。
トークンをソースやコマンド引数へ貼らない。`gh auth token` / `--show-token` を使ってログへ表示しない。
`gh auth status` はスクリプトでも確認し、未認証なら実行を止めてセットアップを案内する。

GitHubの `lolipop-production` EnvironmentとFTPS Secretsは事前設定が必要。スクリプトはSecretsの値を取得せず、ローカルへ保存しない。

## コマンド

リポジトリ直下から:

```sh
./scripts/deploy.sh dry-run
```

GitHub上のmain HEADを取得し、既存ワークフローへmode=dry-runで一度だけdispatchする。実行完了を待ち、成功ログから全送信予定を表示し、SHAと実行IDをローカルGit管理ディレクトリ内の `tuougm-deploy/last-dry-run.json` に記録する。
保存するのはrepo名・SHA・実行IDのみ。失敗・main更新時は成功記録を更新しない。ログ全文や認証情報は保存しない。
Dry Runも本番FTPSへ接続・一覧取得するが書込みは行わない。

```sh
./scripts/deploy.sh deploy
```

これは人間が対話端末で実行する。以下の順序で確認する。

1. gh認証と対話可能な標準入力・標準出力を確認。
2. ローカル作業ツリーがクリーンで、HEADとGitHub mainが一致することを確認。
3. ローカル成功記録があれば、そのSHAとmainが一致しなければ拒否。
4. 記録がなければGitHubの直近100件から現在SHAの成功Dry Run候補を探す。
5. 必ずGitHubから実行状態・SHA・main・workflow_dispatch・workflow pathとログを再取得し検証する。ローカル記録だけでは許可しない。
6. mode=dry-run、コミット、固定転送先、Dry Run完了のログ証跡と全PLANを表示する。
7. repo、SHA、転送元・先を表示し、バックアップ完了を確認するよう案内する。
8. 人間本人が `DEPLOY <表示された40桁SHA>` を完全一致で入力する。yes、空入力、非対話実行は承認にならない。
9. 承認後も成功Dry Runとmainを再検証し、`mode=upload`、`reviewed_sha=<SHA>`、`confirmation=DEPLOY TUOUGM5pjax` で一度だけ起動する。
10. Actions完了まで待機しsuccess以外は失敗終了する。

mainがdispatch直前に変わる競合に対してはローカルで再照合し、dispatch時点の差分は既存ワークフローのreviewed_shaチェックでも拒否する。
新たな実行でmainが更新されていた場合は、新しい `dry-run` を行う。スクリプトはcommit/push/fetch/checkoutを自動実行しない。

## 承認の扱い

Codexは本番アップロードの承認文字列を代入力しない。入力のパイプ、疑似端末による自動入力、承認スキップのオプションは使わない。Codexの指示だけでの反映は禁止。
非対話環境では安全に停止するので、ユーザー自身のターミナルでdeployを実行する。
GitHub EnvironmentのRequired reviewersが設定されていれば別途その承認が必要。スクリプトは承認APIを呼ばず、待機するだけ。

## 実行IDの特定と制限

既存ワークフローに一意トークンを追加せず、dispatch前後の実行履歴の差、認証ユーザー名、modeを含む既存run-name、SHA、branch、eventで照合する。
同じユーザーが同じmodeを同時に起動すると識別が曖昧になるため、複数候補を検出したら停止する。GitHub Web画面や別端末から並行dispatchしないこと。既存run-nameでは完全な一意相関を保証できないため、この運用制約が必要。
候補探索は最大120秒、完了待ちは最大2時間。履歴は直近100件、削除・期限切れのログは使えない。対象証跡が見つからない場合は安全に停止する。
Dry Runログは全ファイル上書き予定であり、内容差分や本番の変更を固定するものではない。本番の同時編集を避ける。

## 失敗時

自動再実行・再アップロード・キャンセルは行わない。
通信失敗がdispatch後に起きると、ローカルが失敗でもActionsがすでに起動している場合がある。表示された実行URL、またはActions一覧を必ず確認してから次の操作を判断する。Ctrl+Cでもサーバー側の実行はキャンセルされない。
GitHubログ取得に失敗した場合も生のログや認証エラー詳細は出力せず停止する。バックアップ復旧は既存FTPS運用資料に従う。

## オフラインテスト

```sh
bash -n scripts/deploy.sh
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s scripts/tests -v
```

テストはgh呼び出し・人間入力・ローカル状態をモック化し、認証・main更新・履歴照合・承認・dispatch入力・失敗時の非再試行を確認する。実GitHub・FTPSには接続しない。実際のgh認証、ログ形式、Environment待機、ネットワーク、実dispatchは別途レビュー後のDry Runで確認する。

実装: `scripts/deploy.sh`（入口）、`scripts/deploy.py`（履歴照合と実行制御）、`scripts/tests/test_deploy.py`。

## 公式資料

- [gh workflow run](https://cli.github.com/manual/gh_workflow_run)
- [gh run list](https://cli.github.com/manual/gh_run_list)
- [gh run view](https://cli.github.com/manual/gh_run_view)
