# AI Design Director 設計仕様書 v1.0

## 1. プロジェクト概要
AI Design Director は、WordPress 上で記事生成・画像生成・SEO最適化・サイトプロファイル分析を統合する AI 制作支援プラットフォームです。

## 2. 開発目標
- Gemini API 最新版への対応
- AIプロバイダーを切り替え可能な設計
- WordPress Coding Standards 準拠
- PHP 8.1+
- 拡張性・保守性を重視

## 3. システム構成

```text
ai-design-director/
├── ai-design-director.php
├── includes/
│   ├── class-add-api-client.php
│   ├── class-add-http-client.php
│   ├── class-add-model-manager.php
│   ├── class-add-response-parser.php
│   ├── class-add-prompt-builder.php
│   ├── class-add-logger.php
│   └── class-add-config.php
├── admin/
├── assets/
├── templates/
└── logs/
```

## 4. クラス責務

| クラス | 役割 |
|---|---|
| API Client | AIサービスとの通信 |
| HTTP Client | 共通HTTP・リトライ・タイムアウト |
| Model Manager | モデル管理・切替 |
| Response Parser | テキスト・画像・エラー解析 |
| Prompt Builder | プロンプト組み立て |
| Logger | ログ出力 |
| Config | 設定管理 |

## 5. 処理フロー

### 記事生成
1. サイトプロファイル取得
2. プロンプト生成
3. AIへ送信
4. レスポンス解析
5. 記事保存

### 画像生成
1. 記事要約
2. 画像プロンプト生成
3. Gemini画像生成
4. メディア登録
5. アイキャッチ設定
6. alt / caption 自動生成

## 6. 管理画面

- Dashboard
- Article
- Image
- SEO
- Originality Booster
- Site Profile
- Logs
- Settings

## 7. AI機能ロードマップ

### Phase1
- API基盤
- Gemini対応
- ログ
- モデル管理

### Phase2
- 管理画面刷新
- UX改善

### Phase3
- FAQ生成
- JSON-LD
- メタ情報
- 内部リンク
- AIリライト
- SNS投稿生成

### Phase4
- MCP対応
- OpenAI / Claude切替
- ワークフロー自動化
- チーム利用

## 8. MCP対応方針

- MCP Server連携
- Figma
- Slack
- Teams
- GitHub
- Google Drive
- Notion

## 9. 非機能要件

- キャッシュ対応
- APIリトライ
- デバッグログ
- 例外処理統一
- セキュリティ（Nonce・Capability・Sanitize・Escape）

## 10. 将来ビジョン

AI Design Director を、SEO・デザイン・コンテンツ制作・運用まで一元管理できる制作支援プラットフォームへ発展させる。
