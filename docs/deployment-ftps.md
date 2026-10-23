# TUOUGM テーマのFTPSデプロイ

作成日: 2026-10-23。設定レビュー待ち。コミット・push・Secrets登録・本番接続・本番アップロードは未実施。

## 対象と安全境界

- Workflow: `.github/workflows/deploy-theme-ftps.yml`
- Repository: `hayuka/TUOUGM`
- Branch: `main` のみ（別ブランチの実行はジョブをスキップ）
- Source: `themes/TUOUGM5pjax/`
- Destination: `/tuougm/wp-content/themes/TUOUGM5pjax/`（FTPログインから見える絶対パス）
- GitHub Environment: `lolipop-production`

`workflow_dispatch` だけを定義している。push、PR、スケジュールでは起動しない。初期モードは `dry-run`。`upload`、確認文字列、レビューしたコミットSHAの一致がそろった場合だけアップロードする。処理は同時に1件、実行中の転送を次の実行で自動キャンセルしない。

ソースと転送先はワークフロー内に固定し、入力やSecretsから変更できない。転送先へCWDし、PWDも一致することを確認する。存在しない場合は停止し、対象ルート自体を作らない。FTPアカウントのルートと想定パスが合わない場合も停止するため、管理画面またはFTPSクライアントで先に配置を確認する。

テーマ以外のplugins・DB・uploads・リポジトリルートの設定は送らない。ソースのsymlinkや秘密情報・移行データ候補（.env、.sql、.wpress、.pem、.key、.log）は検出時に停止する。.DS_Storeは除外。古いJS/PHP等もテーマ配下の通常ファイルなら送信対象となる。

## FTPS方式

Python標準ライブラリのFTP_TLSを使用し、21番へ接続後に明示的AUTH TLS、ログイン、PROT Pの順で通信する。制御・データ通信とも暗号化し、パッシブモードを使用。TLS 1.2以上、標準CAとホスト名による証明書検証を必須とする。平文FTPへのフォールバックや証明書検証無効化は行わない。

サーバーにはMLSD（構造化ディレクトリ一覧）と、アップロード後のSIZE取得が必要。非対応や権限不足は失敗終了する。ロリポップでの実接続互換性は未検証。

## 必要なSecrets

GitHub → Repository Settings → Environments → `lolipop-production` を作成し、次をEnvironment secretsへ登録する。GitHubに直接入力し、コード・文書・Issue・チャットには値を書かない。

| Secret | 内容 |
| --- | --- |
| `FTPS_HOST` | ロリポップが案内するFTPSホスト名のみ。スキーム・パス・ポート番号は含めない |
| `FTPS_USERNAME` | 対象アカウントのFTPユーザー名 |
| `FTPS_PASSWORD` | 対象アカウントのFTPパスワード |

同名のRepository secretsでも参照可能だが、管理場所はEnvironmentへ統一することを推奨。FTPポートと転送先のSecretは不要。
Environmentのdeployment branchesをmainに限定し、利用可能ならRequired reviewersと自己承認防止を設定する。これらはワークフローだけでは作成・有効化されない。未設定の場合でもupload入力・確認文字列・SHAのチェックは働く。Dry Runも同じEnvironmentを使うため、Environment承認を有効にするとDry Runでも承認が必要。

FTPのデバッグログは有効にしない。Secretsはステップ環境変数経由で渡し、コマンド引数やURLへ埋め込まない。エラー詳細にはサーバーが接続情報を返す可能性があるため、ログには例外の型と一般的な案内だけを出す。ログにはファイル名・サイズ・対象コミット・固定転送先が出る。

## 同期方式とDry Runの意味

初版は**ローカルテーマの全対象ファイルを送信予定にする保守的な方式**。サーバーと内容が同じファイルも再送する。サイズ・更新日時だけで差分を判断して同サイズ変更を取りこぼす方式にはしていない。Dry Runは内容差分ツールではなく、全送信予定の確認である。

- `PLAN CREATE`: サーバーの同パスにファイルがない。
- `PLAN OVERWRITE`: 同パスの既存ファイルを上書き予定。
- `PLAN MKDIR`: 存在しないテーマ内サブディレクトリの作成予定。
- サーバー側だけにあるファイルは残す。DELE・RMD・renameを呼ばない。
- Dry Runでは接続・認証・CWD/PWD・MLSDだけを実行する。STORとMKDはクライアント側でも拒否する。
- uploadではSTORで上書きし、各ファイルのサイズを確認。途中失敗も非ゼロ終了する。

「削除なし」は上書き禁止を意味しない。STOR中の切断で対象ファイルが途中までの状態になる可能性があり、テーマ全体の転送は原子的ではない。自動ロールバックも行わない。本番バックアップと作業時間帯の確保が必要。テーマに音源・画像が多いため、全送信に時間・転送量がかかる。古いファイルの残存は運用上も考慮する。

## 実行手順

### 事前準備

1. 設定をレビューし、明示的な承認後にmainへコミット・pushする（今回未実施）。workflow_dispatchはデフォルトブランチ上のワークフローを使う。
2. SecretsとEnvironmentを設定する。初回はuploadを選ばない。
3. FTPSから見える対象ディレクトリが既に存在することを確認する。
4. ローカルまたは本番仕様資料でPHPバージョンを確認する。現在は開発環境に合わせPHP 8.2 CLIで全テーマPHPを構文チェックする。構文チェックはWordPressやテーマを実行しないためDBには接続しない。本番PHPとの互換性は別途確認する。

### 初回と通常のDry Run

1. GitHub Actions → `TUOUGM theme FTPS deployment` → Run workflow。
2. Branchはmain、modeは既定の `dry-run`。残りの入力は空でよい。
3. Environment承認がある場合は承認する。
4. PHP構文チェックとFTPS一覧取得が成功したことを確認する。
5. 実行ログの全 `PLAN` と対象コミットSHAを確認・控える。転送先、件数、意図しないファイルがないか確認する。

初回の作業はここまで。Dry Runであっても本番への接続自体は行うが、書込みは行わない。

### 明示的な本番アップロード

本番バックアップを確保し、直前のDry Runをレビューしてから、新しい手動実行で次を指定する。

| 入力 | 指定 |
| --- | --- |
| Branch | main |
| mode | upload |
| reviewed_sha | 直前Dry Runで確認した40桁の完全なコミットSHA |
| confirmation | `DEPLOY TUOUGM5pjax` |

実行時点のmainが変わっていればSHAチェックで停止する。変更後のmainでDry Runをやり直す。SHA一致は人がレビューした同じコードを選ぶための照合であり、過去Dry Runの成功・バックアップ作成をAPIで自動証明するものではない。

PHP構文チェックの後、再び本番一覧を取得し、その時点の計画でアップロードする。Dry Run後の本番手修正は守られないため、並行編集を避ける。
終了後は本番の日英トップ、ログイン、MEMBERS、音楽再生、PJAXを確認する。PHP構文チェックだけでは画面やデータ互換性を保証しない。

## 既存テーマのバックアップと復旧

1. 証明書を検証するFTPSクライアントを、ロリポップの案内どおり明示的TLS・ポート21で接続する。
2. `/tuougm/wp-content/themes/TUOUGM5pjax/` 全体を、日時を付けたローカル保管先へダウンロードする。デプロイ先の中や公開Web領域にはバックアップを置かない。
3. ファイル件数・サイズ・転送完了を確認し、重要なPHP/CSS/JS/画像/音源が読めることを確認する。可能なら取得ファイルのハッシュ一覧も保管する。
4. 実行予定コミットSHA、取得日時、対象パスをバックアップに添えて保管する。FTP認証情報は含めない。
5. 変更前の主要画面と動作を確認してからuploadする。

失敗時は追加デプロイを止め、同じ固定パスへバックアップの内容をFTPSで戻す。このワークフローにはローカルバックアップを自動復旧する機能はない。上書き復旧でも新規追加ファイルは残るため、削除が必要な場合は別途一覧を確認して明示的に判断する。Gitの以前のコミットは本番の手修正を含まないため、FTPSバックアップの代用にはならない。

## 検証結果と未検証事項

ローカルのダミーデータ・模擬FTPで実施（本番接続なし）:

- YAML解析、全runステップのbash構文、workflow_dispatch限定、dry-run既定、contents:readを確認。
- 既存PHP 8.2開発コンテナでテーマ全35 PHPファイルの構文チェックを通過（DB実行なし）。
- YAMLから取り出したインラインPythonの構文を確認。
- Dry RunでSTOR/MKDなし、削除呼び出しなしを確認。
- TLS制御/データ保護の順序と証明書検証有効を確認。
- 接続・TLS・対象ディレクトリ不在・PWD不一致・MLSD失敗で書込みせず失敗終了することを確認。
- mode=uploadだけでは接続すら行わず、確認文字列とSHA一致が必須であることを確認。
- 模擬uploadがテーマ内の対象だけを送り、転送失敗・サイズ不一致で失敗終了することを確認。
- 別ブランチとソースsymlinkで停止し、ダミー認証情報が標準出力・標準エラーに含まれないことを確認。

未検証: 実際のGitHub Actions runner、Secrets/Environment設定、ロリポップのTLS/MLSD/SIZEとパス・権限、実ネットワークでのDry Run/転送、本番PHPバージョン、本番バックアップ復元。初回は実uploadを行わずDry Runで互換性を確認する。

## 参照

- [GitHub Actions ワークフロー構文](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax)
- [Python ftplib / FTP_TLS](https://docs.python.org/3/library/ftplib.html)
- [ロリポップ 明示的FTPS設定](https://lolipop.jp/manual/hp/w-ff-ftps/)
