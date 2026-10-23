# TUOUGM サイト開発

TUOUGMのWordPressサイト開発用リポジトリです。本番バックアップ取り込み後のテーマ・プラグインをローカルで開発します。

## 現在の環境

2026-10-23確認。有効テーマは `TUOUGM5pjax`、WordPress 6.5.5、PHP 8.2.21、MySQLイメージは8.0です。

- サイト: http://localhost:8088/
- 管理画面: http://localhost:8088/wp-admin/
- GitHub: https://github.com/hayuka/TUOUGM

## ドキュメント

- [環境構築・構成](docs/environment.md)
- [開発手順・確認項目](docs/development.md)
- [バックアップ・移行・復旧](docs/backup-and-migration.md)
- [トラブル対応](docs/troubleshooting.md)
- [環境整備・修正履歴](docs/change-history.md)
- [エージェントの作業方針](AGENTS.md)

## 起動・停止

Docker Desktopを起動し、このリポジトリ直下で実行します。

```sh
docker compose config --quiet
docker compose up -d
docker compose ps
```

停止は `docker compose stop`、再開は `docker compose start` です。
新しいPCでは先に `.env.example` から `.env` を作り、2つのDBパスワードを別々のランダム値に変更してください。既存 `.env` は上書きしません。

## 配置

```text
themes/                     WordPressテーマ（現在の開発対象はTUOUGM5pjax）
plugins/                    WordPressプラグイン
docker/php/uploads.ini      PHP設定
docker/apache/uploads.conf  Apache設定
docs/                       開発・運用ドキュメント
archive/ai-article-plugin/   転用前のファイル保管先
.env                        ローカル認証情報（Git対象外）
.env.example                設定例
```

DBとuploadsは専用Dockerボリュームに保存されています。Gitのcloneだけでは現在のサイトデータは復元されません。
コミット・pushはユーザーの明示的な指示後に実施します。
