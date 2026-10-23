<?php
/**
 * Plugin Name: AI SEO Assistant
 * Description: WordPressでAI記事を自動生成し、下書き保存するプラグイン
 * Version: 0.1.0
 * Author: AI SEO Assistant
 * Text Domain: ai-seo-assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AISEO_ASSISTANT_VERSION', '0.1.0' );
define( 'AISEO_ASSISTANT_FILE', __FILE__ );
define( 'AISEO_ASSISTANT_DIR', plugin_dir_path( __FILE__ ) );
define( 'AISEO_ASSISTANT_URL', plugin_dir_url( __FILE__ ) );

require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-prompt-builder.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-ai-client-interface.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-gemini-client.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-openai-client.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-claude-client.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-ai-client-factory.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-site-profile-analyzer.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-originality-booster.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-image-provider-interface.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-gemini-image-provider.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-openai-image-provider.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-stability-image-provider.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-image-provider-factory.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-image-service.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-media-analyzer.php';
require_once AISEO_ASSISTANT_DIR . 'includes/class-aiseo-image-shortcode.php';
require_once AISEO_ASSISTANT_DIR . 'admin/class-aiseo-admin.php';

function aiseo_assistant_init() {
    $shortcode = new AISEO_Image_Shortcode();
    $shortcode->register();

    if ( is_admin() ) {
        new AISEO_Admin();
    }
}
add_action( 'plugins_loaded', 'aiseo_assistant_init' );
