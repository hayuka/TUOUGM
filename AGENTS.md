# TUOUGM 開発方針

- このリポジトリはTUOUGMのWordPressサイト開発用。AI記事生成プラグインの開発プロジェクトではない。
- テーマは `themes/<theme-slug>/`、サイト専用プラグインは `plugins/<plugin-slug>/` に配置する。現在の有効テーマは `themes/TUOUGM5pjax/`。過去テーマと混同せず、対象を確認して編集する。
- `archive/ai-article-plugin/` は既存ファイルの保管先。明示的な指示がなければ変更・削除・現環境への有効化をしない。
- 既存Dockerコンテナ・ボリューム・データを削除しない。`docker compose down -v`、`docker volume rm`、`docker system prune` などのデータ削除操作はユーザーの明示的な指示なしに行わない。
- 開発用Composeはルートの `docker-compose.yml`。プロジェクト `tuougm-site` の専用ボリュームを使用する。旧aiseoボリュームへ接続しない。
- DB接続設定と秘密情報は `.env` に置く。値をログ・回答・Gitへ出力しない。`.env.example` はダミー値だけにする。
- WordPressコアとuploadsはDockerボリュームに保管する。リポジトリにコピーしない。
- 変更後は `docker compose config --quiet`、`git diff --check` と変更に応じたPHP構文・表示確認を行う。日本語・英語のフロント、関連テンプレート、ログイン画面を確認する。
- コミット・pushはユーザーから明示的に依頼されるまで行わない。

- 環境構成と運用手順は `docs/` を参照する。構成や運用を変更した場合は関連ドキュメントも更新する。
- `plugins/ai-seo-assistant/` は移行元サイトから戻った有効プラグイン。保管先の旧実装とは区別する。

## 本番デプロイの承認

- `scripts/deploy.sh dry-run` もGitHub Actionsを起動し本番FTPSへ接続する。実行の依頼がある場合のみ起動する。実装・テスト依頼だけでは起動しない。
- 本番反映には対象SHAと送信予定を人間が確認したうえで、その実行について明示的に承認することが必須。
- `scripts/deploy.sh deploy` の承認文字列は人間本人が端末で入力する。Codexが代入力、入力のパイプ、疑似端末での自動承認、確認フラグの迂回をしてはならない。TTYを使えること自体は人間の承認の証拠ではない。
- 人間の入力を受けられない実行環境では停止し、ユーザー自身の対話端末での実行を案内する。GitHub Environmentの承認をAPI等で代行・迂回しない。
- 成功したDry RunのSHAとmainの一致をGitHub上で検証する。main更新・履歴不明・ログ取得不可時は新しいDry Runが必要。
- commit・push・再デプロイ・Actions再実行は別途の明示的な依頼なしに行わない。失敗時は自動で本番転送を再実行しない。
