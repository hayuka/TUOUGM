# WordPress データ・プラグイン依存

2026-10-23、コードの静的解析。実DBの投稿件数、ACF定義・値、ユーザー情報は未取得。以下のフィールドは「参照される名前」であり、管理画面の定義を確認したものではない。

## 1. 投稿タイプとURL

主定義は `themes/TUOUGM5pjax/functions.php`。

| 投稿タイプ | 関数・行 | 内容 |
| --- | --- | --- |
| post（標準） | register_post_type_args:231 | ラベルを最新情報へ、has_archive/rewriteをarchives、REST有効 |
| gallery | create_post_type:263 | public、archive、title/thumbnail/excerpt。専用テンプレートなし |
| story | tuougm_register_story_post_type:301 | public/REST、storyスラッグ、title/editor/thumbnail/excerpt/author/revisions |
| music_album | tuougm_register_music_cpts:642 | public/REST、albumsスラッグ、title/editor/thumbnail/author、archive |
| music_track | 同上:676 | public/REST、tracksスラッグ、title/editor/thumbnail/author、archive |

musicデータはテーマ定義であり、テーマ切替で登録が失われる。投稿そのものが削除されるわけではないが、管理画面・URL・APIが使えなくなる可能性がある。

## 2. タクソノミー

| 名前 | 対象 | 階層 | rewrite |
| --- | --- | --- | --- |
| gallery-cat | gallery | あり | 明示指定なし |
| story_category | story | あり | story（階層URL） |
| story_timeline | story | あり | timeline |
| story_type | story | あり | type |
| related_character | story | なし | character |
| related_location | story | なし | location |

storyとstory_categoryは同じstoryスラッグ。`tuougm_story_request_filter()`（439–469）がREQUEST_URIの最後のslugを見て投稿を先に検索し、なければ分類を解決する。通常の階層解決とは異なり、同名slug、多階層、別言語URLの挙動は要確認。

## 3. ACFと投稿メタ

### Album / Track

参照: `inc/music-data.php:15–116`。

| フィールド | 所属・用途 | コードが期待する形式 |
| --- | --- | --- |
| album_title_en | albumの英語名 | 文字列想定 |
| album_type | album種別 | 値形式は要確認 |
| required_level | albumのアクセス段階 | 整数へcast。未設定値も0相当になる |
| album_tracks | albumの曲順 | 配列、要素はIDまたはWP_Post |
| track_audio | track音源 | ACF Fileの配列/url、添付ID、URL文字列に対応 |
| track_title_en | track英語名 | 文字列想定 |
| track_type | track種別 | 値形式は要確認 |
| track_external_url | 外部リンク | URL想定 |

アルバムtitleは投稿タイトル、artworkはアイキャッチ、release_dateは投稿日、descriptionは本文。track.lyricsはtrack本文をHTML除去・改行整形したもの。album_tracksの並びが再生順を決める。get_track_dataにはpublish確認がない。

### Story

`story_single.php:10` の `subttl`、同:62の `related1` 等を参照。related1〜related5はループにより動的名を使い、各値からlink/linktext/blankを取り出すグループ配列を期待する。`timeline_year`、`timeline_order` はstory.php/story_history.php/story_single.phpでget_post_metaにより参照する。
フィールドの正確な型・必須指定・関連先・表示条件・返却形式・ACFグループIDは要確認。主テーマ内にローカルACF JSONや `acf_add_local_field_group()` 定義は確認できず、DB管理である可能性があるが断定しない。

## 4. 会員データと認可

| データ | 保存・読込 | 用途 |
| --- | --- | --- |
| membership_level | user meta | RESTのアルバム公開範囲、会員本文、管理者編集 |
| sin_level | user meta、匿名時Cookie | 段階別コンテンツ解放 |
| pass | フォーム入力 | SIN LEVEL解放コードに照合。値は転記しない |

membershipは0匿名/未設定、1 MEMBER、2 SUPPORTER、3 PREMIUMという箇所と、管理者編集画面のARCHIVIST/KEEPERという表記が混在。`functions.php:836`の編集UI、:902の保存はmanage_optionsで制限するが値の許容範囲を限定していない。

`page-members.php:28–65`: 匿名時はCookieのsin_levelをそのまま整数化、ログイン済みでmeta未設定の場合はCookie値をmetaへ保存する。GETアクセスでもこの書込みが生じ得るため、本調査では画面を開いていない。
`page-request-access.php` は新規ユーザーへCookieのSIN値を継承。Cookieに署名やサーバー照合は確認できず、アクセス境界としての妥当性は要検討。
更新（2026-10-23、レビュー待ち）: `page-upgrade-access.php` の固定キー判定・membership更新・フォームは撤去。GET/POSTとも303で `/members/` へ転送する。`page-members.php` の不要なmembership用コード定義とUPGRADE ACCESS導線も撤去。SINコードと管理画面の設定は維持。

SIN本文のinclude先はmemberbody1..4、会員本文はmemberbody_pre1..3。親テンプレートの変数に依存する。HTMLを表示しない制御と、画像/音源URLへのアクセス認可は区別する必要がある。

## 5. API・Ajax・ショートコード

- REST `/wp-json/tuougm/v1/player`: functions.php:972。公開GET、アルバム単位でmembership判定。詳細は [audio-player.md](audio-player.md)。
- 標準CPT REST: story/music_album/music_trackでshow_in_rest=true。本文・タイトル・その他レスポンスの公開範囲は要確認。
- Ajax `wp_ajax_tuougm_track_inserter_list`: inc/tuougm-track-inserter.php:406。nonce `tuougm_track_inserter` をcheck_ajax_refererで検証しedit_postsを要求。publish/private/draftのmusic_trackを全件検索。nopriv登録はない。
- `[tuougm_track id="..." label="..."]`: 公開trackか確認しエスケープ済みbuttonを返す。album認可はJSカタログに委ねる。
- functions.phpの `[url]` `[title]` `[tmp_dir]` `[get_tmp]` `[clear]` `[sub]` `[block]`。get_tmpはテンプレート断片を呼べるため本文からPHP部分が到達する入口。blockの属性出力はescape不足候補。
- 外部 `[bogo]` はheader.php:131、`[aiseo_image]` はplugins/ai-seo-assistant/includes/class-aiseo-image-shortcode.phpで登録。

## 6. 管理UI・フック

functions.phpはinit（CPT/分類）、request（Story解決）、admin_init（subscriber転送）、show/edit_user_profileとプロフィール更新（レベル）、manage_users_*（一覧）、wp_mail_from/from_name、wp_enqueue_scripts（不要資産除去）、widget_text（shortcode実行）等を持つ。
track-inserterはmedia_buttons、admin_enqueue_scripts、wp_ajax_*を登録。管理用JS/CSSはPHP内インラインで定義。
旧移行ツールは `tuougm_run_music_migration($dry_run=true)` を持つが現在のfunctions.phpから未読込。dry-runも本調査では実行していない。

## 7. プラグインとの責務分担

| プラグイン | テーマとの関係・確認範囲 |
| --- | --- |
| Advanced Custom Fields | get_fieldの直接依存。停止時music-dataがfatalとなり得る |
| Bogo | 言語切替、locale/filter/shortcode。locクエリの独自上書きも併存 |
| Classic Editor | TinyMCEとmedia_buttons型の曲挿入UI。実際の編集モードは要確認 |
| Post Views Counter | pvc_post_views_displayをfalseへ |
| Custom Post Type Permalinks | 導入済み。テーマ独自requestとrewriteへの影響は設定確認が必要 |
| Autoptimize | 導入済み。直書きJS/CSSの結合・遅延とPJAXへの影響は要確認 |
| Google Site Kit | 導入済み。gtagの供給元候補だがテーマだけでは断定できない |
| WP Mail SMTP | 導入済み。wp_mailフローと送信元フィルターの実効結果は要確認 |
| All-in-One WP Migration | 移行・復元。再生処理の直接依存なし |
| AI SEO Assistant | 記事生成・画像ショートコード。音楽CPT/RESTはこのプラグインではなくテーマにある |
| その他 | Duplicate Page、Intuitive Custom Post Order、WP Multibyte Patch等の配置あり。有効状態・細かい設定は未照会 |

改善候補はデータモデル/認可/RESTをサイト専用プラグインへ分離し、テーマは描画に寄せること。ただし今回は移動・実装変更を行っていない。
