# トラブル対応

## サイドバーのID未指定

症状: `register_sidebar` の引数に `id` がないというNotice。
原因: `functions.php` で引数なしの `register_sidebar()` を呼んでいた。
対応: 有効テーマで `register_sidebar( array( 'id' => 'sidebar-1' ) );` と明示した。
自動割り当てと同じIDを使い、既存ウィジェットの紐付けを維持している。
過去テーマ・保管ファイルには未修正のコードが残る場合がある。

## headers already sent

先行するNoticeやWarningがレスポンスへ出力されると、後続のCookie・リダイレクト用ヘッダーを送れなくなる。
表示された最後のヘッダー警告だけでなく、最初の警告の原因を修正する。PHPファイル先頭のBOMや意図しない出力も確認する。

## Undefined array key "loc"

URLに `loc` がない状態で `$_GET['loc']` を直接参照していたことが原因。
有効テーマの12テンプレートで `$loc = $_GET['loc'] ?? '';` に変更した。
未指定時は `get_locale()`、従来どおり真と評価される値が指定された場合は `en_US` を選ぶ。
この修正は許可する言語値の厳密化ではなく、未指定時の警告解消である。

## ログインできない

1. http://localhost:8088/wp-login.php を直接開く。
2. 警告の画面出力や別ドメインへのリダイレクトがないか確認する。
3. 移行後はユーザー・パスワードも移行元のものになることを確認する。
4. 必要なら対象管理者を確認してローカルだけのパスワードを再設定する。

パスワードはこの文書に保存しない。PHP CLIでWordPressを読み込む場合、BogoがHTTP_HOSTを参照するため、Webと同じホスト情報を設定して確認する。

## 大容量ファイルが取り込めない

`docker/php/uploads.ini` と `docker/apache/uploads.conf`、ディスク空き容量、インポート画面の上限を確認する。
設定変更後はWordPressコンテナの再起動が必要。CLIの `max_input_time=-1` などはWeb用設定と異なることがある。
プラグイン側の対応形式や処理時間、ブラウザの切断も確認する。

## 起動できない

```sh
docker compose config --quiet
docker compose ps
docker compose logs --tail=80 wordpress db
```

ログを共有する前に秘密情報や個人データを除く。旧環境と8088ポートが競合していないか、Docker Desktopが起動しているか確認する。
