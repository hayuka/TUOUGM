# 開発手順

## 作業対象

現在の有効テーマは `themes/TUOUGM5pjax/` です。`TUOUGM2`、`TUOUGM3`、`TUOUGM4` や標準テーマも保管されています。
過去テーマや `functions_old.php` は、使用箇所を確認せず一括置換しません。
サイト共通機能は必要に応じて `plugins/<slug>/` で実装します。

`archive/ai-article-plugin/` は転用前の保管資料です。一方、バックアップから戻った `plugins/ai-seo-assistant/` は現在有効なプラグインです。両者を混同しないでください。

## 日常の手順

1. `git status --short` で既存変更を確認する。
2. `docker compose up -d` で開発環境を起動する。
3. 対象テーマ・プラグインを編集する。マウント経由で即時反映される。
4. 変更したPHPの構文、画面、関連機能を確認する。
5. 変更内容と確認結果を記録する。コミット・pushは依頼後に実施する。

```sh
docker compose config --quiet
git diff --check
docker compose exec -T wordpress php -l /var/www/html/wp-content/themes/TUOUGM5pjax/functions.php
```

## 表示確認

- 日本語トップ `/` と英語切り替え `/?loc=en_US`。
- 記事・カスタム投稿・一覧・404など、変更したテンプレート。
- ログイン画面と管理画面。
- PC・スマートフォン表示。
- 警告・Notice・JavaScriptエラー、PJAXによる画面遷移。
- キャッシュの影響がある場合はAutoptimizeとブラウザのキャッシュを確認する。

## 現在有効なプラグイン

2026-10-23確認。今後の変更時は管理画面で再確認してください。

- Advanced Custom Fields
- AI SEO Assistant
- All-in-One WP Migration
- Autoptimize
- Bogo
- Classic Editor
- Custom Post Type Permalinks
- Duplicate Page
- Google Site Kit
- Intuitive Custom Post Order
- Post Views Counter
- WP Mail SMTP

ローカルDBには移行元の連携設定が含まれる可能性があります。メール送信・外部APIなどの動作確認は対象設定を確認してから行います。

## Gitで扱うもの

テーマ・必要なプラグイン・Docker設定・ドキュメントが対象です。`.env`、DBダンプ、バックアップ、個人データ、APIキーは含めません。
取り込み済みプラグインには実行時生成ファイルが含まれる可能性があるため、`git add .` の前に対象を確認してください。
