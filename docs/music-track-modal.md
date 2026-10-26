# 曲詳細モーダル（Phase 2）

## 構成

- `inc/music-track-detail.php`: 独立ページとREST共通のデータ生成・公開状態・所属アルバム・会員レベル判定。
- `template-parts/music/track-detail.php`: 既存の詳細本文を移動。独立ページの要素・クラス・IDは維持し、RESTのみ専用IDを使用。
- `single-music_track.php`: 既存のmain、見出し、一覧への戻る導線、404処理を維持。
- `page-music.php`: 通常のhrefを維持し、アルバムと曲のID属性だけを追加。
- `js/music-track-modal.js`: 専用のModaal inline枠と非同期取得、一覧DOM順のPREV/NEXT。
- `css/music-track-modal.css`: 既存詳細CSSを再利用する外枠、ナビゲーション、SP全画面用CSS。
- `functions.php`: 共通処理読み込みと専用アセット・REST設定の登録。

既存のPJAX、Swup Head Plugin、プレイヤー、Modaal配布物は変更していない。

## リクエスト・アクセス範囲

`GET /wp-json/tuougm/v1/track-detail?track_id=ID&album_id=ID&locale=ja|en_US`

パラメーターは必須。正の整数IDと許可したlocaleのみ受け付ける。公開済み・パスワードなしの曲とアルバム、ACF `album_tracks`の所属、既存の`tuougm_player_access_level()`と`required_level`を確認する。アクセス不可・所属不一致は共通の404。明示されたアルバムが不可の場合に別アルバムへ切り替えない。

レスポンスは共通部品のHTML、曲ID、アルバムID、曲名、言語別の独立ページURL。音源データを含めない。成功・アクセス不可レスポンスとも`private, no-store, max-age=0`。ブラウザは同一オリジンのCookieとREST nonceを使用し、HTMLの永続キャッシュを持たない。

独立ページでは従来のクエリ条件・フィルター・順序を維持し、最初の閲覧可能な所属アルバムを使う。モーダルはクリック元のアルバムを指定する。

詳細とプレイヤーAPIは同じ会員レベル・公開アルバム条件を使用する。ただし既存のWordPress添付ファイルURLには、この判定によるファイル配信制限がない。既知の音源URLへの直接アクセスを保護する実装は本変更に含まない。既存のCPT REST公開範囲の再設計も対象外。

## Modaal・フォーカス・背景

既存の動画用`.modal`と別のcontrollerを一度だけ生成する。Modaal標準の開閉・Esc・背景クリック・overflow制御・フォーカス初期移動を利用する。標準の閉じる処理は非表示controllerへ復帰するため、after_closeで実際のクリック元へ戻す。

追加処理は`aria-modal`、現在の曲見出しとの関連、背景のinert状態の保存・復元、Tab境界の補完、曲切り替え後の見出しフォーカス、背景スクロール位置の復元。Escの独自ハンドラーや別のbody固定処理は追加していない。同時に複数のModaalを開かない。

専用JSの初期化成功後だけ曲リンクに`data-no-swup`を設定する。通常クリックのpreventDefaultは実際のModaal枠生成を確認してから行う。未初期化・起動失敗・修飾キー付きクリックは通常リンクを維持する。

PREV/NEXTはクリック元の`.music_track_list`に表示された順序だけを使用する。端点ではdisabledにし、別アルバムへ進まない。表示切り替えは再生処理を呼ばない。明示的な再生ボタンだけ既存のdocument委譲ハンドラーを使う。

URL・history・headは変更しない。「曲のページを開く」は新しいタブで独立ページを開く。モーダル内のアルバムリンクは既存の通常リンクを維持する。

Swupの`page:view`で対象リンクを再確認し、`visit:start`でモーダル終了を待つ。進行中の取得を中断し、古い応答は世代番号でも拒否する。失敗・タイムアウトは再試行・独立ページへのリンクを提示し、自動遷移しない。

## 開発環境での検証方法

テストDBにはInnoDBの開発用DBを使用する。PHPテストは一時投稿をトランザクションで作成し、finallyでROLLBACKする。既存投稿は変更しない。本番では実行しない。外部副作用のあるプラグインを有効にした環境では実行前にテスト環境を分離する。

クラウド例（リポジトリルートから）:

```sh
git show origin/main:themes/TUOUGM5pjax/single-music_track.php > /tmp/tuougm-original-single.php
docker compose -f docker-compose.yml -f /workspace/tuougm-setup/cloud-compose.json cp /tmp/tuougm-original-single.php wordpress:/tmp/tuougm-original-single.php
docker compose -f docker-compose.yml -f /workspace/tuougm-setup/cloud-compose.json cp tests/music-track-detail.php wordpress:/tmp/tuougm-detail-test.php
docker compose -f docker-compose.yml -f /workspace/tuougm-setup/cloud-compose.json exec -T -e TUOUGM_UI_FIXTURES=/tmp/tuougm-ui-fixtures.json wordpress php /tmp/tuougm-detail-test.php
docker compose -f docker-compose.yml -f /workspace/tuougm-setup/cloud-compose.json cp wordpress:/tmp/tuougm-ui-fixtures.json /tmp/tuougm-ui-fixtures.json
node tests/music-track-modal.cjs
```

PHPテストでは`TUOUGM_WP_LOAD`と`TUOUGM_BASELINE_SINGLE`でパスを指定可能。比較元は本変更前の独立ページテンプレートを使用する。ブラウザテストにはPlaywrightとChromiumが必要。`TUOUGM_BASE_URL`、`CHROMIUM_PATH`、`TUOUGM_UI_FIXTURES`で接続先等を指定可能。

ブラウザは実際のWordPressページ・JS・CSS・Modaal・Swupを使用し、曲一覧と詳細取得通信だけをテスト用レスポンスに置き換える。詳細HTMLはPHPテストが本物の共通テンプレートで生成したもの。実DBの投稿を残さずUIを検証する方式であり、実投稿を使ったHTTP経由の統合テストとは区別する。

## 検証結果と制約

2026-10-26、WordPress 6.7系 / PHP 8.2 / Chromiumのクラウド環境で確認。

- 共通処理: 公開、非公開、曲とアルバムのパスワード保護、所属不一致、会員レベル、クリック元アルバム優先。
- 独立ページ: 日本語・英語のmain内HTML（タグ間空白を除外）とtitle・canonical・OGPが変更前と一致。
- REST: 成功、404、無効localeの400、専用ID、音源データ・script非包含、no-store。
- UI: 一覧DOM順、先頭・末尾・1曲だけの端点、前後曲名、Tab・Esc・左右キー、フォーカス復帰、背景位置復元、head・プレイヤーDOM維持。
- 通信: 失敗と再試行、応答順序の逆転、タイムアウト、閉じた後の遅延応答破棄。
- Swup: 再訪後も1クリック1リクエスト、開いた状態からの遷移終了処理。
- SP: 390×844のChromiumで全幅・全高。専用JS未初期化時は独立ページへの通常遷移。
- 実ページ: 日本語・英語トップ、MUSIC、ARCHIVESの表示とJS実行エラーなし。

未検証: 本番DB・uploadsでの表示、実音源での再生継続・曲送り・ループ、実HTTP認証による会員レベル別の詳細表示、Safari/iOS実機のスクロール・フォーカス・画面回転、スクリーンリーダー、既存動画と交互に開く操作、すべてのプラグイン組み合わせ。Font AwesomeとGoogle Fontsのクラウド通信制限は既知のまま。
