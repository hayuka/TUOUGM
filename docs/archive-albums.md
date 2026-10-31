# 記事内アーカイブ音源

## データとアクセス制御

`music_album`のACF Text `artist_name`はtrimし、空なら`THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN`を使用する。ACF True/False `archive_only`は`(bool) get_field(...)`で正規化し、未設定の既存アルバムはfalse、RESTではJSON booleanとなる。

`tuougm_get_album_data()`はアーカイブも返す。既存`GET /wp-json/tuougm/v1/player`の`albums`にも、会員レベルが許可する通常・アーカイブの両方を保持する。`required_level`判定・private/no-storeは変更していない。新規API・再生開始時の追加通信はない。

アーカイブ分類は非公開・認可ではない。公開アーカイブはAPIから取得可能。音源ファイルURL自体への直接アクセス制御は既存のままであり、この変更でファイル認可を追加しない。

## 表示と操作の比較

| 操作 | 通常アルバム | アーカイブ |
|---|---|---|
| MUSIC / LATEST RELEASE | 表示対象 | 非表示 |
| プレイヤー選択・初期選択 | 対象 | 対象外 |
| `[tuougm_track id="123"]` | 指定曲を再生 | 指定曲を再生 |
| 同じ曲ボタン再クリック | 既存の再生／一時停止 | 同じ |
| 曲途中の自然終了 | 次曲 | 同じアルバムの次曲 |
| 最終曲、repeat OFF | 次の通常アルバムへ循環 | 停止 |
| 最終曲、repeat ON | 同じアルバム先頭 | 同じアルバム先頭 |
| 最終曲の手動Next / Media Session Next | repeatに関係なく次の通常アルバム | repeatに関係なく同じアルバム先頭 |
| Previous | 既存仕様 | 既存仕様（アルバム外へ出ない） |

アーカイブだけのカタログでは初期選択・音源ロード・自動再生をしない。記事内ボタンのユーザー操作を待つ。アーカイブの再生中も通常アルバムだけを選択リストに表示し、通常アルバムを選べば従来どおり切り替わる。アーカイブ再生中は選択ラジオがなくても、曲一覧内の再生中マークを更新する。

取得・記事内検索は全カタログ、表示・通常連続再生は`getNormalAlbums()`、アルバム末尾の手動送りは`getNextAlbum()`を使用する。自然終了だけはアーカイブ・repeat OFFで既存の停止処理へ進む。既存の曲順、空アルバムの扱い、通常Previous、再生ガード、repeat制御は維持。

Media Sessionは現在アルバムのartist_nameを使用し、古いAPI等で欠けても従来名へフォールバック。GA4の`music_play`、パラメーター、playingイベントからの送信・重複抑止は変更なし。PJAX、歌詞、曲挿入ショートコード・管理用検索も変更なし。曲IDが複数アルバムに属する場合、記事内ボタンは従来どおりカタログで最初に見つかった所属を使う。

LATEST RELEASEは従来の公開条件・並び順を維持する。`archive_only`が未設定（NOT EXISTS）または`'0'`／空文字の通常作品をmeta_queryで絞り、既存required_level条件とANDで結合して1件だけ取得する。PHP側の分類・権限再確認も残す。既存の独立した曲詳細URL・モーダルはarchive_onlyを理由に制限しない。

## 変更ファイル

- `inc/music-data.php`: 2フィールドをデータに追加。
- `functions.php`: APIのアーカイブ／認可の関係をコメントで明示。認可ロジック変更なし。
- `js/audio(5)-track-trigger.js`: 通常表示・循環のフィルター、末尾境界、artist、アーカイブ曲ハイライト。
- `page-music.php`: 通常一覧から除外。
- `front-page.php`: LATEST RELEASEの通常候補を探索。

非稼働の`audio.js`、曲挿入、GA4、PJAX、モーダル、CSSは変更しない。

## 自動テスト

開発環境専用。PHPテストはInnoDBを確認してSTART TRANSACTIONし、finallyでROLLBACKする。True/False/TextのACF定義はテストのPHPリクエスト内だけで登録し、DBのACF設定を変更しない。既存投稿・本番データは変更しない。本番で実行しない。

```sh
docker compose -f docker-compose.yml -f /workspace/tuougm-setup/cloud-compose.json cp tests/archive-albums.php wordpress:/tmp/archive-albums-test.php
docker compose -f docker-compose.yml -f /workspace/tuougm-setup/cloud-compose.json exec -T wordpress php /tmp/archive-albums-test.php
node tests/archive-player.cjs
node tests/archive-player-audio.cjs
```

NodeテストはPlaywrightとChromiumが必要。`TUOUGM_BASE_URL`、`CHROMIUM_PATH`で接続先を指定可能。PHPは`TUOUGM_WP_LOAD`でWordPressのパスを指定可能。

### 検証結果（2026-10-31、クラウド）

- 静的確認: 稼働JS、全3経路の境界、曲挿入管理UIがアーカイブを除外しないこと、既存GA4・PJAXロジック未変更。
- PHP/WordPress統合: artist trim/空欄・未設定、archive true/false/未設定とJSON型、APIに両分類を含むこと、匿名・会員レベルによる絞り込み、no-store、ショートコード、MUSIC、先頭アーカイブを飛ばすLATEST RELEASE。
- Chromium＋模擬Audio/Media Session/API: 選択・初期状態、記事内ボタン、再生/停止、シーク、歌詞・曲名・アルバム表示、ハイライト、全3経路の末尾とrepeat、1曲だけのアルバム、通常循環、通常への切り替え、GA4パラメーター・重複抑止、PJAX同一Audio/DOM保持、追加APIなし。
- 実HTMLAudioElement: リポジトリ内の既存MP3をテスト用アーカイブカタログで再生し、デコード・MediaMetadataのartist・PJAX後の再生時刻継続・自然終了での次曲・末尾停止・手動Nextの同アルバム循環を確認。サンプルは既存TUOUGM音源であり、LOST FIGHTSの実音源を検証したものではない。
- 既存のPHP曲詳細テスト・モーダルブラウザテストを実行。PHP/JS構文・Compose config・git diff --checkを確認。

## 手動確認手順

1. 検証環境で通常アルバム2件、公開アーカイブ2曲、1曲だけのアーカイブ、required_level付きアーカイブを用意する。
2. artist_nameにLOST FIGHTS等を設定し、通常作品のartistは空欄も試す。
3. MUSIC・LATEST RELEASE・選択リストからアーカイブが消え、投稿編集画面の曲挿入検索には残ることを確認する。
4. 記事内ボタンでアーカイブを再生し、曲名・アルバム・ジャケット・歌詞・再生時間を確認する。
5. 途中／末尾のNext、自然終了、repeat ON/OFF、1曲だけの動作を比較表どおり確認する。
6. OSのメディア操作でartistとPlay/Pause/Previous/Nextを確認する。
7. 再生中にPJAXでページ移動し、通常アルバムへの切り替えも試す。
8. 匿名／権限不足／権限ありでAPIと記事内再生を確認し、GA4 DebugViewでmusic_playの受信と重複を確認する。

## 未検証・制約

本番ACF設定・DBは未変更。LOST FIGHTS等の本番音源、実HTTP認証による会員別再生、OSロック画面・実機Media Session操作、Safari/iOS、GA4サーバーでの受信、すべてのプラグイン組み合わせは未検証。模擬Audioの検証と実Audioの検証は上記のとおり区別する。クラウドのFont Awesome/Google Fonts等の既知の外部通信制限は本変更の対象外。

## PRレビュー対応: LATEST RELEASEの取得件数

ACF True/Falseのフォーム値はチェック時1・未チェック時0。実際の開発DBで`update_field(true)`は`'1'`、`update_field(false)`は空文字として保存され、文字列`'0'`もfalseとして取得されることを確認した。古いアルバムはメタ行なしでもfalseとして扱う。この3つのfalse状態をクエリ条件に含める。`NUMERIC != 1`等の広い数値変換は使わない。

`required_level`の未設定または数値0以下という既存条件、公開・パスワードなし、`menu_order ASC / ID DESC`は維持。クエリは`posts_per_page=1`と`no_found_rows=true`を使用し、アーカイブを取得した後に除外する方式ではない。

追加テストで実保存値・未設定・両false表現・権限不足の先行候補・menu_order・同順位でのID降順と、実際のSQLの`LIMIT 0, 1`を確認した。テストデータはROLLBACKし、既存のPHP・模擬Audio・実Audio・モーダルテストを再実行。

取得・展開するアルバムと曲データは1作品分に抑えられる。ただしpostmetaのOR条件と並び替えをDBが評価するため、DB内部の走査量まで一定になるわけではない。本番データでの実行計画・負荷測定は未実施。通常のACF単一値保存を対象とし、独自フィルターや不正な重複メタ等は想定外。設定・DB構造・インデックスの変更は行っていない。
