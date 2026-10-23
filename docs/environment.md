# 環境構築・構成

## 実行環境

2026-10-23の実機確認値です。更新後は再確認してください。

| 項目 | 設定・確認値 |
| --- | --- |
| Composeプロジェクト | `tuougm-site` |
| WordPressイメージ | `wordpress:6.5-php8.2-apache` |
| 実行中WordPress | 6.5.5 |
| 実行中PHP | 8.2.21（64bit） |
| DBイメージ | `mysql:8.0` |
| 有効テーマ | `TUOUGM5pjax` |
| 公開先 | `127.0.0.1:8088` |
| WordPressコンテナ | `tuougm-site-wordpress-1` |
| DBコンテナ | `tuougm-site-db-1` |

WordPressコアは永続ボリューム内にあるため、イメージ指定と実際のバージョンは必ずしも一致しません。

## 初回準備

Docker DesktopとDocker Composeを用意します。リポジトリ直下で `.env` が存在しない場合のみ実行してください。

```sh
cp .env.example .env
chmod 600 .env
```

`.env` の `MYSQL_PASSWORD` と `MYSQL_ROOT_PASSWORD` を別々のランダム値に変更します。
`MYSQL_DATABASE`、`MYSQL_USER`、`WORDPRESS_PORT` もここで設定します。値を回答・ログ・Gitに載せません。

```sh
docker compose config --quiet
docker compose up -d
```

新規ボリュームの場合はWordPressのインストール画面が開きます。既存サイトを再現する場合は別途バックアップの復元が必要です。
初期化済みDBのパスワードは `.env` の変更だけでは更新されません。

## 保存先

| データ | 保存先 |
| --- | --- |
| WordPressコア・uploads・wp-config.php | `tuougm_site_wordpress_data` |
| DB | `tuougm_site_db_data` |
| テーマ | ホスト `themes/` → `/var/www/html/wp-content/themes` |
| プラグイン | ホスト `plugins/` → `/var/www/html/wp-content/plugins` |

テーマ・プラグイン全体が書き込み可能なマウントです。管理画面での追加・更新・移行による上書きはホスト側にも反映されます。

## 大容量アップロード

| 設定 | 値 |
| --- | --- |
| upload_max_filesize | 4G |
| post_max_size | 5G |
| memory_limit | 512M |
| max_execution_time / max_input_time | 3600秒（Web用設定） |
| Apache LimitRequestBody | 0（Apacheの容量制限なし。PHP側で制限） |
| Apache Timeout | 3600秒 |

PHP設定は `docker/php/uploads.ini`、Apache設定は `docker/apache/uploads.conf` です。
ファイルの値を変えた場合は `docker compose restart wordpress`、マウント構成を変えた場合は `docker compose up -d wordpress` で反映します。
CLIでは実行・入力時間の表示がWebと異なる場合があります。

## デバッグ

Composeで `WORDPRESS_DEBUG=1` を指定しています。取り込み済みの `wp-config.php` に独自の定義がある場合は実際の設定も確認してください。
警告は原因のコードを直します。開発設定をそのまま本番へ適用しません。
