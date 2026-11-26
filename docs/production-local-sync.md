# 本番WordPressをローカルへ同期する手順

本番環境を投稿・設定・メディアの正本、Gitをテーマ・独自開発コード・lock指定の第三者製プラグイン実体・環境構築設定の正本として扱います。本番への書き込みは禁止です。DB・会員情報・メディアを含むバックアップはGitへ追加しません。

## 2026-11-25の同期・環境更新状況

- 本番から受領したAll-in-One WP Migrationの`.wpress`をローカルへ取り込みました。インポート前のパッケージ検証とCRC検証は成功し、DBのURL置換はAll-in-Oneのシリアライズ対応インポートに任せました。本番には接続・書き込みしていません。
- 本番とローカルの目標バージョンを揃えました。ローカルのComposeイメージは`wordpress:7.1.5-php8.3-apache`、稼働PHPは8.3.37、WordPress本体とDBスキーマは7.1.5です。公式配布の存在は[WordPress 7.1.5リリース](https://wordpress.org/news/2026/11/wordpress-7-1-5-release/)と[Docker Official Imagesのwordpressタグ一覧](https://github.com/docker-library/official-images/blob/master/library/wordpress)で確認しました。
- 既存の`/var/www/html`ボリュームは維持されるため、Composeイメージの変更だけではコアが更新されませんでした。イメージを取得してWordPressコンテナだけ再作成し、管理画面のコア更新を実行してからDB更新を完了しました。DB・WordPressボリュームの削除や再作成はしていません。
- Dockerサービスは`tuougm-site`です。DBボリューム`tuougm_site_db_data`、WordPressボリューム`tuougm_site_wordpress_data`、ホスト限定ポート`127.0.0.1:8088:80`を維持しています。現時点でDBはhealthy、WordPressは稼働中です。
- Contact Form 7 6.2.6を有効化し、ローカルフォームのテスト送信が成功表示になることを確認しました。MUプラグインの`pre_wp_mail`遮断は維持され、外部メールは送信されません。Site Kitは引き続き無効です。[CF7 6.2.6の公式要件](https://contactform7.com/2026/11/23/contact-form-7-626/)にあるPHP 8.3以上・WordPress 7.1以上を満たしています。
- 既存テーマのPHPファイルは構文チェックを通過しました。PHP設定は`display_errors=Off`、`log_errors=On`です。更新後のWordPressコンテナログにはWarning、Notice、Fatal、SSL接続エラーは見つかりませんでした。
- ローカル更新前の追加退避先は`backups/local-upgrade-before-2026-11-25/`（DBとWordPressサイトファイル、合計約4.2GB）、同期前退避先は`backups/production-local-sync-2026-11-25/`（合計約1.0GB）、受領`.wpress`は`backups/incoming/`（約2.2GB）です。いずれも`.gitignore`対象で、復元確認が終わるまで削除しません。
- Dockerは`./themes`と`./plugins`をWordPressへbind mountしています。インポートで第三者製プラグインの追跡ファイル変更・未追跡ファイルが生じています。旧テーマ`TUOUGM2`〜`TUOUGM4`はGit未追跡であることを確認し、今回ユーザーの指示に基づいてローカルから削除しました。プラグイン差分は変更していません。

## 1. 事前確認とバックアップ

1. `git status --short -uall`で既存のソース変更・未追跡ファイルを記録します。意図せず上書き・削除しないでください。
2. `docker compose config --quiet`と`docker compose ps`を実行し、`tuougm-site`の対象サービスとボリュームを確認します。旧プロジェクトや別ボリュームへ接続しないでください。
3. DB、WordPressボリューム、Git管理外で同期対象にするファイルを`backups/`へ退避します。アーカイブの終了コードに加え、`gzip -t`と`tar -tzf`等で読み取り可能なことを確認します。バックアップ容量・チェックサム・作成日時を記録します。
4. `.env`や認証情報をコピー・表示・ログ出力しません。

Dockerボリューム・DBを削除して作り直す操作（`docker compose down -v`、`docker volume rm`、`docker system prune`等）は行わないでください。

## 2. 本番データの用意

ユーザーが本番管理画面からAll-in-One WP Migrationのエクスポートを行い、`.wpress`をローカルのGit ignore対象`backups/incoming/`へ配置します。可能ならAdvanced Optionsで**Exclude themes**と**Exclude plugins**を選び、Git管理コードやローカル配置物への上書きを防ぎます。本番への操作はエクスポート等の読み取りに限ります。

`.wpress`が使えない場合は、ホスティング画面からDB全テーブルと`wp-content/uploads/`を取得します。同期が必要なプラグイン実行ファイルは、対象・版・配布元を確認し、Git管理ディレクトリを直接上書きしない隔離先へ取得します。テーマはGit版を維持します。

## 3. ローカルへの取込とURL

1. `.wpress`のサイズ・チェックサムを記録し、All-in-Oneのパッケージ検証とCRC検証を完了します。
2. WordPress管理画面でローカルDBを置き換える操作であることを確認してインポートします。テーマ・プラグインを除外していない場合、bind mount先にファイルが展開されるリスクがあります。
3. All-in-Oneのシリアライズ対応URL置換を使います。シリアライズ値を壊すSQL文字列置換や`sed`でDBを一括置換しません。
4. SQLだけを取り込むケースでは、ローカルDBに対してWP-CLIの`search-replace --precise`を使い、必ず`--dry-run`の件数を確認してから適用します。`guid`列は置換しません。本番DBにWP-CLIを接続しないでください。
5. `home`、`siteurl`、公開ページURLがローカルURLを指すことを確認します。

## 4. WordPress・PHPバージョンの更新

- 本番の実バージョンを管理画面またはホスティング情報で確認します。HTTPレスポンスヘッダーだけの情報は補助根拠として扱います。
- 公式WordPressイメージのタグと配布済みWordPressリリースを確認し、ComposeのPHP系統・Webサーバーを維持したタグを選びます。バージョンタグを明示し、`latest`は使いません。
- 更新前にDBとWordPressボリュームをバックアップし、`docker compose config --quiet`を通します。
- `docker compose pull wordpress`後にWordPressサービスだけ再作成します。既存WordPressボリュームは保持します。
- ボリューム内のWordPressコアが目標版かを確認します。Composeイメージ更新だけでボリューム内コアが更新されない場合は、管理画面の「更新」からコアを更新し、表示された場合はDB更新も完了します。
- まずステージング相当のローカルでプラグイン・テーマの互換性、管理画面、フォーム、フロントを確認します。互換性が確認できないプラグインを勝手に更新せず、停止して報告します。
- 完了後、コンテナ内のPHP版、WordPress版、DB更新状態、必要なプラグインの有効状態を確認します。

## 5. ローカル固有の安全設定

同期DBには本番のメール・解析・Webhook設定が含まれ得ます。ローカル表示確認の前に以下を確認します。

- ローカルMUプラグイン`wp-content/mu-plugins/tuougm-local-sync-safety.php`はWordPressメールAPIの`wp_mail()`を短絡し、外部向け送信を止めます。
- WordPress HTTP APIはlocalhost、Docker内サービス、WordPress.orgの更新確認・配布ホストだけを許可し、その他の外部宛リクエストを拒否します。TLS証明書検証を無効化しません。
- Site Kitはローカルでは無効にします。直接ソケット通信や独自Webhookなど、MUプラグインのHTTP APIフィルターを通らない外部通信は別途確認します。
- PHPは`display_errors=Off`、`log_errors=On`、WordPressは`WP_DEBUG_DISPLAY=false`、`WP_DEBUG_LOG=true`を維持し、Warningを画面に出さずログへ記録します。
- CF7の互換性が確認できた環境では、ローカル送信のテストを行えます。`wp_mail()`が遮断されることを先に確認し、実在するメールアドレスや個人情報を使いません。画面上の送信成功は、外部配送成功を意味しません。
- これらはローカル限定です。本番の設定・DB・ファイルは変更しません。

## 6. 管理画面ログインの確認・復旧

同期後、`http://localhost:8088/wp-login.php`が開くこと、`/wp-admin/`のリダイレクト先がローカルであること、既存管理者が存在して管理者ロールを持つことを確認します。ユーザー名・パスワード・Cookie値をログや文書へ記録しません。

ログインできない場合、先に`home` / `siteurl`、`WP_HOME` / `WP_SITEURL`、独自ログインURL、`COOKIE_DOMAIN`、`FORCE_SSL_ADMIN`、ログイン制限プラグインを調べます。既存管理者のパスワード再設定が必要ならローカルDBのみで行い、新しい認証情報をGit・手順書・ログに保存しません。メールリセットに依存しないでください。本番アカウントや本番DBは変更しません。

## 7. 回帰テスト

コンテナ起動、`docker compose config --quiet`、`git diff --check`、関連PHP構文、ブラウザー表示、PHPログを確認します。

- 日本語・英語TOP、ABOUT、ARCHIVES、MUSIC、PRESSを直接表示し、言語属性・翻訳リンクを確認
- PCおよびSPの代表幅で横はみ出しがないこと
- Swup/PJAXリンク、言語切替、ブラウザー戻る・進む
- 音楽プレイヤーの再生、停止、曲送り・アルバム連続再生、ページ移動中の再生継続
- MEMBERSゲストロック画面。レベル別会員表示はテストアカウントがある場合のみ行い、作成はしない
- CF7フォーム表示とローカル送信フロー。実メールが外部へ送られないこと
- 管理画面ログイン、プラグイン一覧、更新確認
- 画像・音声読み込み、PHPログ、ブラウザーコンソール
- 解析・外部通知・Webhookが止まっていること

テストデータの作成や外部サービスへの送信を伴う確認は実施せず、未確認事項として記録します。

### 2026-11-25 環境更新後の確認

- Docker: DBサービスhealthy、WordPressサービスrunning、ホスト限定8088ポート。Compose設定検証成功。
- バージョン: WordPress 7.1.5、PHP 8.3.37。DB更新の成功メッセージを管理画面で確認。
- Contact Form 7 6.2.6: 有効化を確認。PRESSの日本語・英語ページにフォームが表示され、日本語版でローカル用の架空テスト値による送信成功表示を確認。外部メール送信はMUプラグインで短絡。
- ブラウザー: 日本語・英語TOP、ABOUT、ARCHIVES、MUSIC、PRESS、MEMBERSを390px幅で確認。横スクロールなし、対象ページは表示され、PRESS英語の`lang=en`を確認。デスクトップ幅1383pxでもTOP/PRESSに横スクロールなし。
- PJAX: MUSICからENへ遷移し、URLと言語属性が英語に変わることを確認。再生中の曲名と再生時間が遷移中・遷移後も進み、戻る操作後も再生状態が継続。
- プレイヤー: トラック再生ボタンで曲名表示と経過時間が進行し、再生中ラベルへ切り替わることを確認。再生出力はブラウザー自動操作環境のため音量・実機スピーカーで確認していません。曲送り・曲終了後の連続再生は未確認。
- MEMBERS: ゲスト画面で認証コード欄が表示され、ロック画面状態を確認。会員ログイン後のレベル別アクセス、ギャラリー操作はテスト会員がいないため未確認。
- PHPテーマ構文チェック成功。WordPressコンテナログからWarning/Notice/Fatal/SSLエラーは検出されませんでした。ブラウザーにはSwup View Transitionの`InvalidStateError`が1件記録されました（/press/遷移時）。他のページ表示・PJAX動作は継続しましたが、この既存警告の原因は未特定です。
- 外部メール遮断・Site Kit無効化・HTTP APIの外部通信制限を維持。直接ソケット通信等の全経路を計測したわけではありません。

## 8. プラグインのGit管理と再構築

Docker Composeは`./plugins:/var/www/html/wp-content/plugins`をbind mountします。プラグインの識別パスやAIOのローカルstorageを変えず、Gitから実体を再現できます。`plugins/ai-seo-assistant/`は独自開発コードとして、lock指定の第三者製プラグイン14件は公式配布版の実体としてGit管理します。Hello Dollyは復元対象にもGit管理対象にも含めません。

第三者製プラグインの名前、バージョン、WordPress.orgからの取得情報、DB上で確認した参考有効状態、公式配布ツリーのSHA-256は`docker/third-party-plugins.lock`に固定しています。新規チェックアウト・再構築時はDockerを起動する前に次を実行し、Git上の実体を検証します。欠損プラグインがある場合だけ公式配布ZIPから復元します。

```sh
./scripts/install-local-plugins.sh
docker compose up -d
```

インストーラーは既存プラグインの完全なツリーをlock内のSHA-256と照合し、一致すればダウンロードせず検証完了します。lockの指紋は全パス、ディレクトリ、シンボリックリンク先、ファイル内容を含みます。欠けているプラグインだけを一時領域へ取得し、ZIP CRC、安全な相対パス、プラグインヘッダーの版、展開した完全なツリー指紋を確認してから、未使用のパスへ配置します。既存パスが不完全・異なる版・内容差・余分なファイルを含む場合は、何も上書きせず停止します。取得元は[WordPress.orgのプラグイン配布ページ](https://wordpress.org/plugins/)と公式配布ホストです。指紋変更やバージョン更新は別レビューが必要です。

Duplicate Page 4.5.9はWordPress.org APIが現行版として返しますが、版別ZIP URLは404を返しました。このため公式の現行ZIP URLを使い、版ヘッダーとlockの完全なツリー指紋の両方を照合します。公開ZIPが差し替わった場合は指紋不一致で停止します。

2026-11-25にHello Dollyの公式ZIPが既存の単体ファイル`plugins/hello.php`と重複し、管理画面に2件表示されました。不要プラグインとして除外する判断を受け、既存ファイルと検証時の重複ディレクトリを削除し、lockの行も削除しました。初回構築・同期のプラグイン復元はlockを読む`install-local-plugins.sh`のみで、Hello Dolly専用の別配置処理はありません。以後の構築・同期ではlockにある14件だけが配置されます。削除前後でHello Dolly以外の有効プラグイン識別子とWP Mail SMTP設定が同一であることを確認しました。

#### Duplicate Page 4.5.9のローカル保全物からの復元

2026-11-25時点の公式ZIPは、Git管理外の`backups/vendor-archives/duplicate-page-4.5.9.zip`に保全しています。対応する`.txt`に取得元・取得日時・版・ZIP SHA-256・展開後指紋を記録しています。これは同じ端末上の保全であり、端末故障・紛失に対する恒久バックアップではありません。外部保管先を用意するまでは、このZIPだけに依存する復元性に限界があります。

`plugins/duplicate-page`が存在しないローカル環境を復元するときは、まず`.txt`記載のSHA-256とZIP CRCを確認します。ZIPを一時ディレクトリに展開し、`duplicate-page/duplicatepage.php`の版が4.5.9であることと、`scripts/plugin-tree-hash.py <展開先>/duplicate-page -`の値が記録および`docker/third-party-plugins.lock`の指紋と一致することを確認してください。一致した場合だけ、展開したディレクトリを`plugins/duplicate-page`へ配置し、その後`bash scripts/install-local-plugins.sh`を実行します。インストーラーが既存ツリーを検証してダウンロードなしで通ることを確認してください。ディレクトリが既にある場合や指紋が異なる場合は上書きせず停止し、既存プラグインの保全手順に従って調査します。保全ZIPの破損・紛失時はWordPress.orgの版別ZIPが利用できないため、公式配布物の取得可否と指紋を改めて確認する必要があります。

不一致時は再実行を繰り返さず、対象ディレクトリ全体をGit管理外のバックアップへ複製してから、公式ZIPとの差分を調べます。追加ファイルがローカルデータかカスタマイズかを確認するまで削除・置換しません。カスタマイズがあれば独自コードとして分離します。AIO Migrationを再設置する場合は`storage/`を維持し、検証済みプラグイン配置後に戻します。lockの指紋をローカル実体に合わせて安易に更新しないでください。

2026-11-25の同期直後にはCPT Permalinks、Site Kit、WP Mail SMTP等で公式ZIPと異なるファイル構成が検出されました。差分調査とバックアップを経て公式版を検証・再配置する運用を行っています。AIO Migrationの`storage/`のみはローカルデータとして指紋対象外で、Gitにも含めません。

| プラグイン | バージョン | 状態 | 取得元 |
| --- | ---: | --- | --- |
| Advanced Custom Fields | 6.8.12 | 有効 | WordPress.org版別ZIP |
| Akismet | 5.3.2 | 無効 | WordPress.org版別ZIP |
| All-in-One WP Migration | 7.112 | 有効 | WordPress.org版別ZIP |
| Autoptimize | 3.1.17 | 有効 | WordPress.org版別ZIP |
| Bogo | 3.9.3 | 有効 | WordPress.org版別ZIP |
| Classic Editor | 1.7.0 | 有効 | WordPress.org版別ZIP |
| Contact Form 7 | 6.2.6 | 有効 | WordPress.org版別ZIP |
| Custom Post Type Permalinks | 3.6.0 | 有効 | WordPress.org版別ZIP |
| Duplicate Page | 4.5.9 | 有効 | WordPress.org現行ZIP（指紋固定） |
| Site Kit by Google | 1.192.0 | 無効 | WordPress.org版別ZIP |
| Intuitive Custom Post Order | 3.2.0 | 有効 | WordPress.org版別ZIP |
| Post Views Counter | 1.8.2 | 有効 | WordPress.org版別ZIP |
| WP Mail SMTP | 4.10.5 | 有効 | WordPress.org版別ZIP |
| WP Multibyte Patch | 2.9.4 | 無効 | WordPress.org版別ZIP |

`plugins/ai-seo-assistant/`は独自開発プラグインとしてGit管理を維持します。新しいチェックアウトでは通常のGit checkoutで復元できます。AIO同期では**Exclude themes**と**Exclude plugins**を選択し、DB・uploadsだけを同期します。`plugins/`はGit版を維持し、プラグイン版の変更はlockと公式ZIP指紋を更新する別工程にします。旧テーマ`TUOUGM2`〜`TUOUGM4`はGit追跡対象0件を確認して2026-11-25にローカルから削除済みです。現行テーマとWordPress標準テーマは保持しています。

プラグインソースのGit追跡は、Composeのbind mount、管理画面の有効・無効状態、DB内のactive plugin識別子を変更しません。インストーラーはファイルの検証・復元だけを行い、有効化や設定のDB変更を行いません。

### 2026-11-25 隔離復元・指紋検証

- Hello Dolly除外前のlockにあった第三者製プラグイン15件について、一時環境で公式配布ZIPからの復元と2回目のダウンロードなし検証を実施した記録です。
- Bogoに意図的な余分ファイルを置いた隔離コピーでは、指紋不一致で停止し、余分ファイルを保持しました。
- Hello Dollyを削除した後、CPT Permalinks、Site Kit、WP Mail SMTPをlock指定の公式版へ差し替え、14件すべての完全指紋検証が通ることを確認しました。curlを失敗させる一時PATHで実行し、ダウンロードが発生しないことも確認しています。
- 管理画面には第三者製プラグイン14件とGit管理下のAI SEO Assistantが表示され、Hello Dollyはありません。AI SEO Assistantの25ファイル、有効プラグイン一覧、WP Mail SMTP設定ハッシュは作業前後で維持されています。外部メールは送信していません。
- `bash -n scripts/install-local-plugins.sh`、Pythonヘルパーのコンパイル確認、Compose設定検証、Git差分チェックを実施しました。

### 2026-11-26 第三者製プラグインのGit追跡方針

- 方針を変更し、lock指定の第三者製プラグイン14件の公式版実体をGit管理対象に戻します。`plugins/ai-seo-assistant/`も引き続き追跡します。Hello Dollyは削除状態を保ち、復元しません。
- 初回構築ではGit checkoutでプラグイン実体を取得し、`bash scripts/install-local-plugins.sh`で全14件の完全指紋を照合してからDockerを起動します。欠損時はスクリプトが公式配布物を検証して復元します。不完全・異なる既存ディレクトリは上書きされず停止するため、Git外へ退避して原因を調査してから再実行します。
- `.gitignore`では第三者製プラグイン全体を除外せず、生成依存物、ログ、および`plugins/all-in-one-wp-migration/storage/`だけを除外します。lock、指紋ヘルパー、復元スクリプトもGit管理を維持します。
- 本番同期ではAIOの**Exclude themes**と**Exclude plugins**を選択し、DB・uploadsのみを取り込みます。同期後にローカルのGit管理プラグインを上書きしないでください。DBに保存された有効化状態はそのまま維持し、この手順で有効化・無効化しません。
- `storage/`、`.wpress`、DBダンプ、認証情報、バックアップはGitへ追加しません。ローカルバックアップの保持・最終削除は別途承認に従います。

## 9. SwupのView Transitionログ

環境更新後のブラウザー確認で、`/press/`への遷移時に`InvalidStateError: Transition was aborted because of invalid state. ViewTransition opt-in disabled`が1件記録されました。テーマには`document.startViewTransition()`の呼び出しがなく、Swup 4.10.0の`native`オプションも既定値falseで、環境更新ではSwupコードを変更していません。今回のWordPress/PHP更新が直接の原因という根拠はありませんが、更新前の同一ブラウザー記録がないため既存問題かは確定できませんでした。ページ遷移・言語切替・再生継続の失敗は観測していません。影響が限定的な未特定ログとして別タスク扱いにし、今回修正しません。

## 10. ロールバックと一時バックアップの整理

問題が起きた場合、保存したDB SQLとWordPressサイトアーカイブを使って**ローカルのみ**復元します。ボリュームを削除せず、Compose設定と対象サービスを確認し、復元後にローカル安全設定を再確認します。

一時バックアップは、バージョン更新・DB更新・ページ・メール遮断・ログイン確認の最終承認後に削除します。削除直前に対象ファイル名、実容量、合計容量、復旧に不要となった根拠を報告し、承認を得てから対象ファイルだけを削除します。`.gitignore`の包括的削除や`git clean`は使いません。

今回確認したバックアップの概算は、`backups/local-upgrade-before-2026-11-25/`約4.2GB、`backups/production-local-sync-2026-11-25/`約1.0GB、`backups/incoming/`の`.wpress`約2.2GBです。現時点では削除していません。
