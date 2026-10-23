<?php
/**
 * Provider status card template.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $text_provider_info ) || empty( $text_provider_connection ) ) {
    return;
}

$status_labels = array(
    'success' => array( 'label' => '🟢 利用可能', 'class' => 'provider-status-success' ),
    'warning' => array( 'label' => '🟡 混雑中', 'class' => 'provider-status-warning' ),
    'error' => array( 'label' => '🔴 利用不可', 'class' => 'provider-status-error' ),
);

$status = isset( $status_labels[ $text_provider_connection['status'] ] )
    ? $status_labels[ $text_provider_connection['status'] ]
    : $status_labels['error'];

$provider_icons = array(
    'Gemini' => 'dashicons-admin-site',
    'OpenAI' => 'dashicons-admin-network',
    'Claude' => 'dashicons-admin-users',
);

$provider_icon = isset( $provider_icons[ $text_provider_info['name'] ] )
    ? $provider_icons[ $text_provider_info['name'] ]
    : 'dashicons-admin-generic';

$stars = function( $count ) {
    $count = max( 0, min( 5, (int) $count ) );
    $output = '';
    for ( $i = 1; $i <= 5; $i++ ) {
        $output .= $i <= $count ? '★' : '☆';
    }
    return $output;
};
?>
<div class="aiseo-provider-card">
    <div class="aiseo-provider-card-header">
        <div class="aiseo-provider-card-title">
            <span class="dashicons <?php echo esc_attr( $provider_icon ); ?> aiseo-provider-card-icon"></span>
            <div>
                <strong><?php echo esc_html( $text_provider_info['name'] ); ?></strong>
                <span class="aiseo-provider-card-subtitle"><?php echo esc_html( $text_provider_info['model'] ); ?></span>
            </div>
        </div>
        <button type="button" class="button aiseo-provider-card-change-button">変更</button>
    </div>

    <div class="aiseo-provider-card-body">
        <div class="aiseo-provider-card-row">
            <div class="aiseo-provider-card-label">状態</div>
            <div class="aiseo-provider-card-value <?php echo esc_attr( $status['class'] ); ?>"><?php echo esc_html( $status['label'] ); ?></div>
        </div>
        <div class="aiseo-provider-card-row">
            <div class="aiseo-provider-card-label">安定性</div>
            <div class="aiseo-provider-card-value"><?php echo esc_html( $stars( $text_provider_info['stability'] ) ); ?></div>
        </div>
        <div class="aiseo-provider-card-row">
            <div class="aiseo-provider-card-label">速度</div>
            <div class="aiseo-provider-card-value"><?php echo esc_html( $stars( $text_provider_info['speed'] ) ); ?></div>
        </div>
        <div class="aiseo-provider-card-row">
            <div class="aiseo-provider-card-label">SEO品質</div>
            <div class="aiseo-provider-card-value"><?php echo esc_html( $stars( $text_provider_info['seo'] ) ); ?></div>
        </div>
        <div class="aiseo-provider-card-row">
            <div class="aiseo-provider-card-label">コスト</div>
            <div class="aiseo-provider-card-value"><?php echo esc_html( $text_provider_info['cost'] ); ?></div>
        </div>
        <div class="aiseo-provider-card-row">
            <div class="aiseo-provider-card-label">推奨用途</div>
            <div class="aiseo-provider-card-value"><?php echo esc_html( $text_provider_info['recommended_for'] ); ?></div>
        </div>
        <div class="aiseo-provider-card-note">
            <?php echo esc_html( $text_provider_info['note'] ); ?>
        </div>
    </div>
</div>
<style>
.aiseo-provider-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 20px;
}
.aiseo-provider-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}
.aiseo-provider-card-title {
    display: flex;
    flex-direction: column;
}
.aiseo-provider-card-icon {
    font-size: 16px;
    margin-bottom: 4px;
}
.aiseo-provider-card-subtitle {
    font-size: 13px;
    color: #6b6b6b;
}
.aiseo-provider-card-change-button {
    margin-left: 16px;
}
.aiseo-provider-card-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 24px;
}
.aiseo-provider-card-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.aiseo-provider-card-label {
    color: #555d66;
}
.aiseo-provider-card-value {
    font-weight: 600;
}
.aiseo-provider-card-note {
    grid-column: 1 / -1;
    color: #72777c;
    margin-top: 12px;
}
.provider-status-success {
    color: #21759b;
}
.provider-status-warning {
    color: #d54e21;
}
.provider-status-error {
    color: #a00;
}
</style>
