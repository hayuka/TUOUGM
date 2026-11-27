# TUOUGM iPhoneプレイヤー実機検証

## 公開範囲と仕組み

通常の開発環境は`http://localhost:8088/`で維持します。iPhone向け公開は明示的に開始スクリプトを実行したときだけ有効になり、指定したMacのRFC1918プライベートIPv4に`8089`番ポートをバインドします。`0.0.0.0`、外部トンネル、ルーターのポート転送は使いません。

`docker-compose.iphone.yml`だけで、LAN向けポート、LAN URL環境変数、一時MUプラグイン、およびApacheのCSS応答変換を追加します。MUプラグインはリクエストのHostが指定LAN URLと一致する場合に限り、WordPressの`home`/`siteurl`を実行時にLAN URLとして返し、`localhost:8088`を使うHTML・添付URL・TUOUGMプレイヤーREST応答をレスポンス時だけ変換します。LAN用HTMLではAutoptimize CSSリンクに専用クエリを付け、過去に保存された`immutable` CSSキャッシュの再利用を避けます。Apacheでは`mod_substitute`をLAN用コンテナ起動時だけ有効にし、指定LAN HostからAutoptimize CSSパスへ来た`text/css`応答だけで`http(s)://localhost:8088`または`//localhost:8088`をパス相対の同一オリジンURLに変換し、その後gzip圧縮します。Host値は開始スクリプトが検証したプライベートIPv4から渡し、任意のリクエストHostを置換先に使いません。HTML以外の他の静的レスポンス、REST、音源ストリームにはApache置換を適用しません。CSSキャッシュファイル自体は書き換えません。DB値やシリアライズデータは書き換えません。REST APIのNonceは通常と同じoriginから取得されます。

既存のローカル安全MUプラグインはそのまま維持します。メール送信遮断と外部HTTP通信制御を弱める変更はありません。外部解析通信がないことはiPhone接続後にWeb InspectorのNetworkで確認してください。

## 開始

1. MacとiPhoneを信頼できる同一Wi‑Fiに接続し、ゲストネットワークや端末間通信が遮断されるネットワークを避けます。
2. MacのプライベートIPv4を確認します（例：`ipconfig getifaddr en0`。Wi‑Fiインターフェース名は環境により異なります）。
3. macOSファイアウォール等で制限できる環境では、iPhoneのLAN IPからの接続だけを許可してください。LAN上の他端末からもアクセス可能になる点に注意してください。
4. リポジトリルートで次を実行します。

   ```sh
   scripts/start-iphone-test.sh <MacのプライベートIPv4>
   ```

5. スクリプトがRFC1918アドレスとMacのインターフェース割当を確認し、通常の8088ポートを残したまま、LAN用8089を追加します。LAN用Apache起動スクリプトが`mod_substitute`とHost限定CSSフィルターを設定します。既存コンテナはCompose設定変更により再作成される場合がありますが、既存ボリュームは削除・再作成しません。
6. 出力された`http://<Mac-LAN-IP>:8089/`をiPhone Safariで開きます。

LAN公開を開始できない場合やLAN内の端末間通信が制限される場合は、設定を拡大せず停止してください。外部トンネルやルーターのポート転送で代替しません。

## Safari Web Inspector

1. iPhoneで **設定 → アプリ → Safari → 詳細 → Webインスペクタ** を有効にします。
2. 最初はUSBケーブルでMacとペアリングし、iPhone側でMacを信頼します。
3. Mac SafariのDevelopメニューからiPhoneを選び、必要なら **Connect via Network** を有効にします。
4. iPhoneで開いたTUOUGMページをDevelopメニューから選択し、ConsoleとNetworkを確認します。

Appleの現行手順では、Wi‑Fi経由のInspector接続を有効にする前に、Web Inspectorを有効化してケーブルで一度接続します。

## 診断ログの開始・取得

デバッグは既定でOFFです。ログを有効にするには、検証ページURLに`?tuougm_player_debug=1`を付けてiPhoneで開きます。セッション中はSwup遷移後も有効で、ログはこのタブの`sessionStorage`に最大120件保存されます。記録には時刻、曲ID、再生状態、Audio要素の状態、Visibility、MediaErrorコード、Audio Session APIの対応状況を含みます。曲名・歌詞・音源URL・URLクエリやNonceは保存しません。

ブラウザ復帰後、Safari Web InspectorのConsoleで次を実行します。

```js
window.tuougmPlayerDebug.getJSON()
await window.tuougmPlayerDebug.copy()
window.tuougmPlayerDebug.clear()
```

`copy()`はクリップボードが使えない場合にJSON文字列を返します。`clear()`はログと有効化状態を消し、記録を停止します。再度記録する場合はURLに`?tuougm_player_debug=1`を付けて開き直します。sessionStorageが利用できない場合も再生には影響せず、保存ログが空になるだけです。

## 検証ケース

以下を同じiOSバージョン・Safariで個別に試し、iOSバージョン、通常Safariかホーム画面追加アプリか、`ended`、`play-track-start`、`play-track-resolved`または`play-track-rejected`、`playing`イベント、復帰後のcurrentTimeを記録します。

1. 前面表示中のアルバム連続再生。
2. 再生中に別アプリへ切り替えた状態で曲間を迎える。
3. 画面をロックした状態で曲間を迎える。
4. Safari復帰後に診断ログを取得する。
5. ロック画面から次曲を操作する。
6. デバッグOFFとONの通常再生を比較する。
7. REST APIが200でJSONを返し、音源・画像・CSS・JavaScriptが8089経由で読み込まれること、JP/ENとPJAX遷移を確認する。

HTTPはLAN内診断用です。HTTPSの本番条件も別途確認する場合は、有効なローカル証明書を用意し、証明書警告を回避しないでください。

## 終了・復元

1. iPhoneのSafariタブを閉じます。
2. Macで次を実行します。

   ```sh
   scripts/stop-iphone-test.sh
   ```

3. 通常のCompose設定を再適用してWordPressサービスだけを戻します。LAN用Apache設定・`mod_substitute`有効化・MUプラグインのマウントはLAN用コンテナとともに外れます。DB・WordPressボリュームは削除しません。`http://localhost:8088/`が応答し、Composeのポート公開が`127.0.0.1:8088`のみであることを確認してください。
4. `sessionStorage`のログを消すには、デバッグ中タブで`window.tuougmPlayerDebug.clear()`を実行します。タブを閉じるとセッションストレージも破棄されます。

## トラブル時

- iPhoneからページが開かない場合、LAN IP、同一Wi‑Fi、ルーターの端末間通信制限、macOSファイアウォール、Dockerの8089公開を確認します。公開範囲を広げずに終了してください。
- 画面や音源に`localhost`が残る場合はDBを書き換えず、LAN用MUプラグインの対象レスポンス漏れとして記録し、終了手順で通常環境へ戻します。
- 終了時にWordPressが起動しない場合は通常Composeを再度適用します。ボリューム削除や`down -v`は行いません。
