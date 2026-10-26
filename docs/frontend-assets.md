# フロントエンド外部ライブラリ

## 配信と依存関係

`themes/TUOUGM5pjax/functions.php` の `tuougm_enqueue_frontend_assets()` で登録する。

- jQuery: WordPress標準の `jquery` ハンドル。テーマからCDN版を追加しない。
- Modaal: 0.4.4、`vendor/modaal/0.4.4/` のJS・CSS。
- Swup: 4.10.0、`vendor/swup/4.10.0/Swup.umd.js`。従来の `swup@4` と同じメジャー。
- 各vendorディレクトリの `SOURCE.md` に公式npm取得元、パッケージintegrity、コピーした配布ファイルのSHA-256を記録。ライセンスも同梱。
- JS: jQuery → Modaal / Swup → pjax → common → accordion → player → lyrics。各ハンドルに必要な依存と従来の実行順を明示。
- CSS: Modaal → module → style。既存の上書き順を維持。
- プレイヤー設定のnonceは `wp_add_inline_script()` でplayerの直前に出力。
- テーマ資産は更新日時、vendorは固定バージョンでキャッシュを更新する。
- `common.js` と `accordion.js` はjQueryをローカルの `$` として受け取る。グローバル `$` やWordPress標準jQueryの登録は変更しない。

更新時は公式配布物を確認し、バージョン・ライセンス・SOURCE.md・enqueueの指定を一緒に更新する。未確認のCDN代替ファイルを置かない。

## Font Awesome / Google Fonts

既存のKitとGoogle Fontsの読み込みは維持。クラウドでは通信制限で取得できない。
Font Awesome失敗時はSNS等のアイコンが表示されない可能性があり、Google Fonts失敗時はCSSの `sans-serif` にフォールバックして文字幅・改行等が変わり得る。
これらは今回確認したjQuery・Swup・Modaalの初期化を停止させない。フォントやアイコンの置換・ローカル化はデザインとKit内容の確認が必要なため本変更の対象外。

## クラウド確認（2026-10-26）

- WordPress 6.7系 / PHP 8.2、標準jQuery 3.7.1、ACF / Bogo / Post Views Counter有効。
- 日本語トップ `/`、英語トップ `/en/`、MUSIC `/en/music/`、ARCHIVES `/archives/` がHTTP 200。実行エラー・Console警告なし。
- jQuery・Modaal・Swupをローカル取得。グローバル `$` がundefinedの状態で動作。CDN版jQueryの二重読み込みなし。
- モバイル幅でメニュー開閉、トップのModaalインスタンス生成とモーダル枠の開閉を確認。
- プレイヤーメニューのアコーディオンはDOMクリックでクラス切替を確認。ポインターでの操作確認は未完了。
- トップ→MUSICは文書再読み込みなし。MUSIC→ARCHIVES→トップの連続PJAXで同じプレイヤーDOMが保持され、曲トリガーの委譲ハンドラーは各回1件。
- `/wp-login.php` のログイン入力欄表示を確認。認証操作は未実施。
- PHP構文、JS構文、Compose設定、`git diff --check` を確認。

制約: 本番DB・uploadsがなく、アルバム・楽曲が0件。実音源再生、曲送り、アルバムループ、歌詞同期、実データでの曲トリガー再生は未検証。YouTube等の外部通信は制限があるため、モーダル内の動画再生も未検証。PJAX後の既存の再初期化不足は変更していない。会員ページ内のアコーディオンも未検証。有効プラグイン以外の組み合わせや本番での表示は追加確認が必要。
