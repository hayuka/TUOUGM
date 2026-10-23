# AI Design Director リファクタリング計画書

## 概要
既存プラグイン **AI SEO Assistant** を全面的にリファクタリングし、記事生成・画像生成・SEO・サイト分析を統合した **AI Design Director** として再設計する。

## Phase 1
- Gemini API最新版対応
- HTTP通信共通化
- Model Manager
- Response Parser
- Logger
- Config
- API Client

### ディレクトリ構成
```text
ai-design-director/
├── ai-design-director.php
├── includes/
│   ├── class-add-api-client.php
│   ├── class-add-http-client.php
│   ├── class-add-config.php
│   ├── class-add-logger.php
│   ├── class-add-response-parser.php
│   ├── class-add-model-manager.php
│   └── class-add-prompt-builder.php
├── admin/
├── assets/
└── logs/
```

## Phase 2
- Dashboard
- Article
- Image
- SEO
- Originality
- Settings
- Logs

## Phase 3
- オリジナル性ブースター
- FAQ生成
- JSON-LD生成
- メタディスクリプション
- タイトル生成
- AI校正
- AIリライト
- 内部リンク提案
- SNS投稿生成

## Phase 4
記事生成→画像プロンプト→画像生成→メディア登録→アイキャッチ設定→alt生成→caption生成

## 設計方針
- WordPress Coding Standards準拠
- PHP 8.1+
- API依存を最小化
- モデル切替を容易に
- 将来的なOpenAI・Claude対応も考慮

## 最終ビジョン
AI Design DirectorをAI制作支援プラットフォームへ育成する。
