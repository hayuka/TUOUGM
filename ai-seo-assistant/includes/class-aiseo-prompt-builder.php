<?php
/**
 * Prompt builder for AI SEO Assistant.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Prompt_Builder {
    public function get_system_prompt() {
        return "あなたは日本語のSEO記事編集者です。\n" .
            "以下のルールに従って出力してください。\n" .
            "- 読者にとって分かりやすい自然な日本語を使う\n" .
            "- 根拠のない断定を避ける\n" .
            "- WordPress投稿用の記事として作成する\n" .
            "- Markdown見出し記法を使う\n" .
            "- 文章は最後まで切れずに完結させる\n";
    }

    public function get_mode_instruction( $mode ) {
        $modes = array(
            'blog' => "SEO Mode: 検索意図を満たす構成と見出しを作成し、読みやすいSEO記事を生成する。",
            'list' => "List Mode: リスト形式で整理された構成を作り、要点をわかりやすく伝える。",
            'howto' => "Howto Mode: 手順とポイントを明確に示し、読者が実行しやすい構成で説明する。",
        );

        return isset( $modes[ $mode ] ) ? $modes[ $mode ] : $modes['blog'];
    }

    public function get_tone_instruction( $tone ) {
        $tones = array(
            'standard' => "出力トーン: 標準。自然で読みやすく、一般的なビジネス記事に適した表現。",
            'friendly' => "出力トーン: 親しみやすい。会話調で読み手に近い語り口にする。",
            'professional' => "出力トーン: 専門的・信頼。論理的で信頼性の高い表現を使い、専門性を感じさせる。",
            'clear' => "出力トーン: 分かりやすい。構造を整理して、初心者にも理解しやすい表現を優先する。",
        );

        return isset( $tones[ $tone ] ) ? $tones[ $tone ] : $tones['standard'];
    }

    public function build_user_prompt( $keywords, $article_type, $tone ) {
        return sprintf(
            "キーワード: %s\n" .
            "記事タイプ: %s\n" .
            "%s\n" .
            "条件:\n" .
            "- タイトルを1つ提示する\n" .
            "- 導入、見出し、本文、まとめを含める\n" .
            "- 最終出力に \"タイトル:\" / \"本文:\" のラベルを含めない\n" .
            "- HTMLではなく、Markdown見出し記法で出力する\n" .
            "出力形式:\n" .
            "タイトル:\n\n" .
            "本文:\n",
            $keywords,
            $this->get_mode_label( $article_type ),
            $this->get_tone_instruction( $tone )
        );
    }

    private function get_mode_label( $mode ) {
        $labels = array(
            'blog' => 'SEO Mode',
            'list' => 'List Mode',
            'howto' => 'Howto Mode',
        );

        return isset( $labels[ $mode ] ) ? $labels[ $mode ] : $labels['blog'];
    }
}
