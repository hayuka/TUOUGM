# 技術的負債・潜在不具合の評価

2026-10-23。静的解析に基づく候補であり、未再現のものはその旨を記す。重要度は影響範囲と発生条件で評価。実装修正は一切行っていない。パスは `themes/TUOUGM5pjax/` からの相対。

## 優先度上位5件

| ID | 重要度 | 対象 | 問題・影響 | 推奨方針・要確認 |
| --- | --- | --- | --- | --- |
| T01 | High | functions.php:972、inc/music-data.php:68、会員テンプレート | APIはalbumだけで認可し、音源は直接URL。公開music_trackには標準RESTもある。UI非表示だけでは会員資産の配信を防げない可能性 | 限定対象を定義し、REST/添付/静的ファイルまで一貫した認可へ。実URLの匿名アクセスとサーバー側保護は要確認 |
| T02 | High | page-members.php:28–65、page-request-access.php:36、page-upgrade-access.php:26–81 | SINを未署名Cookieから信頼しmetaに保存。membership昇格用の共有キーがソースに直書き、nonce検証なし。認可・解放の迂回やCSRFの可能性 | サーバーで権限判定、署名/サーバー保存、共有キーの撤去・ローテーション、nonceと入力検証。SINが有償権限かは要確認。値は資料に転記しない |
| T03 | High | js/audio(5)-track-trigger.js:setAlbum / playTrack（233–315付近） | setAlbumはalbum/playlist/indexを更新してからplayTrackを呼ぶが、切替中playTrackはreturn。連打時に音声と状態がずれ得る。visibilitychangeもガードを解除 | 遷移を単一の直列処理にし、状態確定タイミング・要求世代ID・取消方針を定義。低速音源と連打で再現試験が必要 |
| T10 | High | page-login.php / page-members.php / page-upgrade-access.php | HTML出力後にCookie・認証・redirect処理。ヘッダー送信失敗の候補 | 出力前の処理へ分離し、フォーム認可を検証。詳細は下記 |
| T11 | Medium | story.php / single-story.php等 | defaultで自己再帰、欠落部分テンプレート、呼出元と部分側のfooter呼び出し重複（同じfooter出力はコアが抑制） | wrapper/fragmentを分離しStory全ルートを実機確認。詳細は下記 |

## 追加の重点候補

| ID | 重要度 | 対象 | 問題・影響 | 推奨方針・要確認 |
| --- | --- | --- | --- | --- |
| T04 | Medium | 同JS:initAudioPlayer（705付近）、front-page.php:412 | APIのIDは文字列、jQuery.dataは数値化し得る。getAlbum/getTrackの厳密比較で旧曲ボタンが見つからない。BANNED曲liにはdata-player-trackがない | 入口でIDを文字列に統一、テンプレートのデータ属性契約を整理。現DBのID一致と各曲ボタンを確認 |
| T05 | Medium | js/pjax.js:38、js/common.js:44、現役JS:759 | PJAX後の再初期化はparallax/進捗/タブのみ。Modaal、会員accordion、旧曲一覧、画像モーダルの直接bindが新DOMに付かない候補。カタログとnonceも初回固定 | init/destroyと委譲の責務を分け、冪等なページ初期化に統合。ログイン・会員変更時のカタログ更新。現在の二重登録を断定せず遷移試験で確認 |

## プレイヤー・イベント・計測

### T06: 次曲仕様の分岐重複

- **重要度:** Medium
- **箇所:** 現役JS `handleEnded()`、initPlayerのnext/prev、`updateMediaSession()`。
- **事実:** ended最終曲はrepeatを尊重するが手動/OS nextは次アルバムへ。通常prevは3秒ルール、OS prevは前曲だけ。末尾アルバムは先頭へ循環。globalIsPlayingAllでendedを制限しない。次アルバムが空ならそれ以降を探さない。
- **影響:** 操作方法で結果が異なり、意図しない循環・停止になり得る。
- **方針:** 仕様表を確定し、純粋な「次の曲を決める」関数へ統合。repeat/単曲/全曲の意味を明示。意図が異なるかは要確認。

### T07: 再初期化時の二重bindリスク

- **重要度:** Medium
- **箇所:** 現役JS `initPlayer()` / `initAudioPlayer()`、accordion.js、common.js、未読込 `tuougm-track-trigger.js`。
- **事実:** initPlayerは現在API成功時一度のみ。Audio生成も単一化されており、通常PJAXで増殖することはコードからは確認できない。一方、多くのonにはoffやinitializedガードがなく、未読込の別triggerにも同じdocument selector/namespaceがある。
- **影響:** 再初期化を追加したり旧JSを併用すると、1クリックで再生→停止、二重曲送り、interval増殖等の可能性。
- **方針:** 読込入口を一つにし、イベントをnamespace化してoff/on、永続UIとページUIを分離。documentの登録数を計測してから修正。

### T08: API失敗・音源不正時の回復不足

- **重要度:** Medium
- **箇所:** 現役JS `loadPlayerData()`、`loadAudio()`、`playTrack()`。
- **事実:** fetch失敗はconsoleのみ。空srcのtrackは既存Audioを返し得る。play失敗はUIフラグ解除のみ。
- **影響:** ユーザーが失敗理由を把握できない。無効src曲を選んだ際に旧音源を再生する可能性。
- **方針:** データスキーマ・src検証、明示的失敗状態、再試行、既存音源の停止方針。欠損データで再現が必要。

### T09: GA4重複抑制とrepeatの意味

- **重要度:** Low
- **箇所:** 現役JS `sendMusicPlayEvent()` / `loadAudio()`。
- **事実:** gaPlayedは同じ曲ではresetしない。repeatで同じ曲を再開しても送信されない場合がある。gtag未定義なら送信を見送る。
- **影響:** 「再生開始回数」とGA集計が一致しない可能性。
- **方針:** 計測単位を仕様化し、同意・PJAX page_view・GA4受信を確認。今回外部計測は実行していない。

## PHP・認証・テンプレート

### T10: 出力後の認証・Cookie・リダイレクト

- **重要度:** High
- **箇所:** page-login.php:11/28、page-members.php:1/103、page-upgrade-access.php:9/43。
- **事実:** get_header/HTMLを先に出し、後からwp_signon・setcookie・wp_redirectを呼ぶ経路がある。page-request-accessはPOSTをheader前に処理しているが、register_nonceを出力するだけで検証は確認できない。
- **影響:** バッファ設定次第でheaders already sent、Cookie未設定、リダイレクト失敗。フォームnonce不足も課題。
- **方針:** template_redirectやadmin_post等で出力前処理へ分離。全フォームのnonce/unslash/バリデーション・エスケープを整理。POSTは今回は実行していない。

### T11: Storyテンプレートの再帰・不足・footer呼び出し責務

- **重要度:** Medium
- **箇所:** story.php:185–216/末尾、single-story.php等、story_top.php:22。
- **事実:** story_categoryのdefaultでget_template_part('story')を再呼び出す。location/archives用断片が不在。story.phpも呼出元もget_footerする。HISTORYリンクにhrf誤記。
- **影響:** 想定外slugで再帰が続く候補、本文欠落、get_footerフックの重複発火、リンクが動かない。同一footerファイルはコアのload_onceで再出力が抑制されるため、これだけでplayer/scriptが二重化するとは断定しない。
- **方針:** wrapper/fragmentの責務を明確化し、defaultを安全な一覧へ、欠落ルートを整理。コアgeneral-template.phpのget_footer/get_template_part読込フラグは読み取り確認済み。Story画面のHTMLとフック実行回数を優先検証。

### T12: PHPへのドメイン責務集中とACFハード依存

- **重要度:** Medium
- **箇所:** functions.php全995行、inc/music-data.php。
- **事実:** 表示調整・CPT・分類・メール・認証補助・メタ・API・管理UIが同テーマ内。get_fieldは存在確認なし。ACF定義のコード管理は確認できない。
- **影響:** テーマ切替やACF停止でデータ機能が停止。回帰範囲を把握しにくい。
- **方針:** 先に振舞いとデータ契約を固定し、CPT/認可/APIを専用プラグインへ段階分離。ACF定義を秘密情報なしでエクスポート・版管理。

### T13: 標準RESTとカタログの公開整合性

- **重要度:** Medium
- **箇所:** functions.php:645/676、inc/music-data.php:39/68。
- **事実:** music_album/music_trackはpublic/show_in_rest。album_tracksはpublish以外のtrackも型だけで整形する。
- **影響:** 公開albumにdraft/private trackを関連付けるとタイトル/歌詞/src等がカタログに出る可能性。
- **方針:** track公開状態・関連付け権限・個別認可を検証し、標準RESTの公開フィールドも確認。実DBの誤関連有無は要確認。

### T14: 周辺の未定義・escape・フォールバック

- **重要度:** Medium
- **箇所:** header.php:20–94、story_characters.php:14、functions.php:blockFunc、index.php。
- **事実:** ttl_og/disc_ogは主にトップ分岐で設定し他ページでも出力。$post_idはcharacters断片内で定義されない。block shortcodeのurl/titleは直接連結。indexは空。
- **影響:** 警告、OGP欠落、不適切な属性出力、汎用フォールバックの白紙化。
- **方針:** 出力に必ず初期値・文脈別escapeを適用し、テンプレートを実URLで検証。未定義値を単に隠す対応にしない。

## CSS・資産管理

### T15: CSSのカスケードと画面責務混在

- **重要度:** Medium
- **箇所:** css/style.css（3517行）、module.css、common.js。
- **事実:** .playerは基礎・3020付近・3396付近で分散。player_title/m_cnt_header/mg_modalも反復。複数のmedia、1170pxのJS accordion、1023pxのparallax条件が混在。768pxでmin/maxが重なる。3474付近に `max-height: auto` という無効候補値。
- **影響:** 上書き順を変えると他画面・端末に回帰。プレイヤー表示が状態クラスとmediaにまたがる。
- **方針:** 単純な重複削除はせず、viewportと状態別のスクリーンショットを先に用意。base/layout/component/pageへ分割し、breakpointとデザイントークンを統一。

### T16: 直書きCDNとWordPress依存管理の分断

- **重要度:** Medium
- **箇所:** header.php:98–105、footer.php:117–131。
- **事実:** 外部jQueryと複数ライブラリを直接挿入しwp_footerを後から実行。Swupはmajor指定のみ。CSS/JSにテーマ版番号が付かない。
- **影響:** WordPress側jQueryとの重複候補、キャッシュ不整合、CDN停止時の全体停止、最適化プラグインの遅延読込との競合。
- **方針:** enqueue/dependencies/versionで統一し、ライブラリ版を固定。実際の最適化設定と二重jQueryの有無は要確認。

### T17: 旧資産と表示内容の二重管理

- **重要度:** Low
- **箇所:** functions_old.php、audio*.js旧版、en-page.php、memberbody_pre3.php、front-page.php。
- **事実:** 旧文字列IDと新CPT ID、ハードコード曲名とAPIデータが併存。旧テーマディレクトリは資産のみ。
- **影響:** 編集対象を誤る、DB更新と画面表示がずれる、不要資産の増加。
- **方針:** 使用箇所とDB本文参照を先に確定し、削除せず整理計画を立てる。アルバム表示も同じデータ契約から生成する。

## 次工程での推奨順序（未実施）

1. 会員資産の実際の公開要件と配信境界、フォーム認可を確認する。
2. Story画面の自己再帰・フック重複と曲送り競合を再現する。
3. 投稿ボタン・旧曲一覧のID契約とPJAX再初期化をテストする。
4. データ・再生ロジックを仕様化してからPHP/JSを段階分割する。
5. CSSは画面比較を用意してから統合する。

DB値、会員操作、外部送信を伴う試験と改善は今回実施していない。
