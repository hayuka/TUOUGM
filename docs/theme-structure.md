# TUOUGM テーマのファイル別責務

調査日: 2026-10-23。主対象は `themes/TUOUGM5pjax/`。以下の相対パスは特記しない限りこのテーマ配下。静的な参照関係とWordPressの命名に基づく到達候補を分けて記す。DBのテンプレート割当は未確認。

## 1. ディレクトリ

```text
TUOUGM5pjax/
  functions.php / functions_old.php
  header.php / footer.php / index.php
  front-page.php / en-page.php / page*.php
  single*.php / archive*.php / taxonomy-*.php / 404.php
  story.php / story_*.php
  memberbody1..4.php / memberbody_pre1..3.php
  inc/ music-data.php / tuougm-track-inserter.php / tuougm-music-migration.php
  js/ プレイヤー・PJAX・共通UI・旧版
  css/ module.css / style.css
  style.css / editor-style.css / admin_post.css
  img/ / audio/ 画像・静的音源
```

## 2. PHPテンプレート

| ファイル | 役割・到達経路 |
| --- | --- |
| `front-page.php` | フロントページ。日英分岐、リリース・アルバム・曲ボタン、投稿紹介。アルバムID・トラックIDが直書き |
| `en-page.php` | `Template Name: en top` を持つ選択用テンプレート。旧カタログの文字列ID `origin` 等が残る。現在の割当は要確認 |
| `page.php` | 固定ページの汎用ループとthe_content。本文内ショートコードが入口になり得る |
| `single.php` | 通常投稿の詳細。共通header/footer、言語判定 |
| `archive.php` | 通常アーカイブ。投稿一覧描画 |
| `404.php` | 404専用画面とアルバム再生ボタン |
| `page-members.php` | membersスラッグ候補。SIN・membership計算、解放コードPOST、Cookie/meta更新、会員本文include |
| `page-login.php` | loginスラッグ候補。wp_signon、成功時リダイレクト。POST処理がheader出力より後 |
| `page-request-access.php` | 会員作成。wp_create_user、sin_level継承、認証Cookie設定 |
| `page-upgrade-access.php` | auth_keyからmembership_levelを昇格。共有コードは資料に転記しない |
| `page-recover-credentials.php` | retrieve_passwordによる再発行メール要求 |
| `single-story.php` | Story詳細入口。`get_template_part('story')`。header/footerも自身で出力 |
| `archive-story.php` | Storyアーカイブ入口。同じくstory.phpを呼ぶ |
| `taxonomy-story_category.php` / `taxonomy-story_type.php` | 分類画面入口。同じくstory.phpを呼ぶ |
| `story.php` | Story共通本文、タイムライン側欄、条件別部分テンプレート振り分け。末尾でget_footerも呼ぶ |
| `story_top.php` | Storyトップ。カテゴリ案内。稼働中のHISTORYリンクに `hrf` 表記あり |
| `story_history.php` | history分類とtypeクエリを条件に一覧・年表を組み立てる |
| `story_characters.php` | 人物一覧。アイキャッチ参照で未定義候補 `$post_id` を使用 |
| `story_single.php` | Story本文、subttl、年代、分類、関連投稿、前後投稿。独自クエリで順序を構築 |
| `memberbody1.php`–`memberbody4.php` | SIN段階別の本文。親の `$unlocked` 等の変数に依存 |
| `memberbody_pre1.php`–`memberbody_pre3.php` | membership段階別のギャラリー・音源等。旧音源IDも残る |
| `memberbody2.php` | 上記の一つ。loc判定も保持 |
| `index.php` | 0バイト。テンプレート階層の最終フォールバックとして内容がない |
| `header.php` | 言語、meta/OGP、canonical、CSS、wp_head、ナビ、`#swup-header` |
| `footer.php` | フッター、常駐.player、CDN/JS、nonce、wp_footer |

`story_location.php`、`story_archives.php` はstory.php:203/207から参照されるが現物がない。`story` を再度get_template_partするdefault分岐もある。未対応カテゴリやフッター呼び出しの重複は [技術的負債](technical-debt.md) を参照。
`gallery`、`music_album`、`music_track` 専用single/archiveは現テーマにない。実際のフォールバック表示は要確認。

## 3. functions.php（995行）の機能分類

| おおよその行 | 責務・主要関数 |
| --- | --- |
| 1–36 | アイキャッチ、画像サイズ、PVC、subscriber管理画面転送、固定ページビジュアルエディタ抑制 |
| 41–67 | sidebar-1、ウィジェットタイトル抑制、Bogo言語名調整 |
| 72–117 | emoji、ブロックCSS、global styles、wp-embed、管理バー抑制 |
| 122–167 | url/title/tmp_dir/get_tmp/clear/sub/blockショートコード |
| 173–224 | pagination、editor-style、TinyMCE、分類チェックリスト |
| 231–298 | 標準postのarchives化、gallery / gallery-cat |
| 301–638 | storyと5分類、requestフィルターによるstory URL解決 |
| 642–706 | `tuougm_register_music_cpts()` |
| 710–917 | ユーザー表示・カラム・メタ保存、wp_mail送信元 |
| 926–995 | music-data読込、catalog、access_level、REST登録、track-inserter読込 |

`functions_old.php` は比較・保管コード。主functions.phpからの読込は確認されない。旧ファイルには移行ツールのrequireがあるが、現在のfunctions.phpにはない。

## 4. inc/

| ファイル | 責務 |
| --- | --- |
| `music-data.php` | `tuougm_get_album_data()` / `tuougm_get_track_data()`、CPT・ACFからAPIデータへ整形 |
| `tuougm-track-inserter.php` | Classic Editorのメディアボタン、インラインCSS/JS、Ajaxの曲一覧、`[tuougm_track]`出力 |
| `tuougm-music-migration.php` | 旧カタログからCPT/ACF/mediaへ移行する管理ツール。現functions.phpからは未読込。調査では実行していない |

## 5. JSの全ファイルと読み込み順

footer.php:117–129の順序は **CDN jQuery → Modaal → Swup → pjax.js → common.js → accordion.js → nonce設定 → audio(5)-track-trigger.js → lyric.js → wp_footer()**。

| ファイル | 現テーマでの明示読込 | 責務 |
| --- | --- | --- |
| `js/pjax.js` | あり | Swup生成、page:view、永続footerのクラス切替、ページ再初期化 |
| `js/common.js` | あり | headerスクロール、メニュー、Modaal、タブ、parallax、進捗、会員accordion、画像モーダル |
| `js/accordion.js` | あり | player menu開閉、アルバム選択パネル開閉 |
| `js/audio(5)-track-trigger.js` | あり | 現役のAPI・Audio・曲送り・repeat・Media Session・投稿曲ボタン・GA4 |
| `js/lyric.js` | あり | DOMのシーク進捗と時間表示から歌詞スクロール、MutationObserver |
| `js/audio.js` | なし | 類似プレイヤー実装。現役と誤認しない |
| `js/audio_old.js` | なし | 旧プレイヤー |
| `js/audio(5)-track-trigger_old.js` / `_old2.js` | なし | 現役に似た旧版 |
| `js/tuougm-track-trigger.js` | なし | 別APIキャッシュとDOMクリック中継による旧投稿ボタン接続 |

「なし」はheader/footer/functionsの明示参照を確認できない意味。DB本文や第三者プラグイン経由の追加読込は要確認。
全読込JSはjQueryに依存。common.js/accordion.jsはグローバル `$` も使う。`window.tuougmSwup`、`window.tuougmInitPage`、`window.tuougmPlayerConfig` が共有入口。`parallaxImg`、`getMemTop`、`updateProgress` はグローバル関数。プレイヤーの `globalCurrent*` は名前にglobalを含むがIIFE内の状態で、windowの変数ではない。

## 6. CSSとUI

header.php:98–105は **Google Fonts → Modaal CSS → css/module.css → css/style.css → wp_head()**。wp_head由来のプラグインCSSは後続になり得る。

| ファイル | 責務 |
| --- | --- |
| `style.css` | テーマメタデータ中心。ここを編集してもメイン画面CSSとは一致しない |
| `css/module.css`（54行） | reset、clearfix、float、文字寄せ等。旧IE向け記述も含む |
| `css/style.css`（3517行） | 全画面の共通・ページ・プレイヤー・会員・Story・responsiveが集約 |
| `editor-style.css` | functions.php:207のadd_editor_style。Twenty Sixteen由来のコメントあり |
| `admin_post.css` | 管理画面用と思われるセレクタ。現PHPからの明示読込は未確認 |
| inserterのインラインCSS | 管理画面の曲選択モーダル。別ファイル化されていない |

共通UIはheader/gnav、flex/inner、ボタン、見出し、player、モーダル。ページ固有はkv・release・album、member、story。
メディアクエリには1250/1200/1170/1020/800/768/750/630pxや769px以上が混在。common.jsにも1170px、1023pxのJS条件がある。768pxでmin/max両方が適用されるブロックがある。
`.player` は基礎定義と3020行付近の追記、3396行付近のモバイル上書きに分散。`.player_title`、`.m_cnt_header`、`.mg_modal` 等の反復も確認。ただし別media条件下の反復をすべて不要な重複とは判断しない。整理時はカスケードと各画面の視覚比較が必要。

補足: コア `wp-includes/general-template.php` をコンテナ内で読み取り確認。get_footerはlocate_templateのload_once=true、get_template_partはfalse。footerの呼び出しが二度あっても同一PHPの再出力は抑制される一方、get_footerアクションは呼び出しごとに発火する。Story部分テンプレートの自己再帰は抑制されない。
