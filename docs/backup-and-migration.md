# バックアップ・移行・復旧

## データの所在

現在のサイトは専用ボリューム `tuougm_site_wordpress_data` と `tuougm_site_db_data`、ホストの `themes/` と `plugins/` の組み合わせです。
Gitの履歴にはDB・uploads・認証情報は含まれません。Dockerボリュームを残すだけでは独立したバックアップにはなりません。

移行前にはDB、WordPressボリューム、themes、pluginsをまとめて保管し、復旧可能なことを確認してください。
SQLやバックアップはGit対象外の `backups/` などへ保存します。保管先には十分な空き容量が必要です。

## All-in-One WP Migration

取り込みに使用したファイル名は `tuougm-zakubaka-com-20261023-101421-33rh779zbm8q.wpress`、確認サイズは2,069,083,752バイトです。ファイル本体はGitへ追加しません。

1. 現在のデータと編集済みテーマ・プラグインをバックアップする。
2. PHP設定・空き容量を確認する（現設定のファイル上限は4GB）。
3. 管理画面の「All-in-One WP Migration → インポート → ファイル」で選ぶ。
4. 上書き対象を確認してから復元する。DB、テーマ、プラグインが置き換わる。
5. 移行元のユーザー認証情報でログインし直す。必要ならローカル管理者パスワードを再設定する。
6. ローカルURL、パーマリンク、日本語・英語、画像、管理画面を確認する。
7. 移行前の設定・コード修正が上書きされていないか確認する。

「バックアップ」画面のRestoreと「ファイルからインポート」は別機能です。導入済みバージョン7.112ではRestoreにUnlimited Extensionが必要と案内されています。ファイルからのインポート上限はPHP設定を参照します。

## 転用前環境の保管

- ファイル: `archive/ai-article-plugin/`
- 旧コンテナ: `tuougm-wordpress-1` / `tuougm-db-1`（停止して保管）
- 旧ボリューム: `aiseo-wordpress_wordpress_data` / `aiseo-wordpress_db_data`
- その他の既存aiseoボリュームも保持する。

旧環境を確認する場合のみ、現在の環境を停止してから明示的に起動します。

```sh
docker compose stop
docker compose -f archive/ai-article-plugin/docker-compose.yml up -d
```

保管Composeの相対マウントは保管先の `tuougm/` を参照します。この起動は旧データに接続し、そこでの操作は旧データを変更します。
終了後は旧Composeを停止して現環境を再開します。

```sh
docker compose -f archive/ai-article-plugin/docker-compose.yml stop
docker compose up -d
```

同じ8088ポートでの同時起動はできません。既存コンテナを単に `docker start` すると移動前のパスを参照する場合があるため、保管Composeを使います。
`docker compose down -v`、`docker volume rm`、`docker system prune` はデータ保管方針に反するため、明示的な削除指示なしに実行しません。
