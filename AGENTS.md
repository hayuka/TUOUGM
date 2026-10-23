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
