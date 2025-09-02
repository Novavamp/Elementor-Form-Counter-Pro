<?php
/**
 * Plugin Name: Elementor Form Counter Pro (Enhanced)
 * Description: Enhanced unique email counting per Elementor form with live updates, CSV export, progress bar, animated counters, admin settings and dashboard widget.
 * Version:     1.3.0
 * Author:      Precious + GPT
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'EFC_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EFC_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EFC_PRO_VERSION', '1.3.0' );

require_once EFC_PRO_PLUGIN_DIR . 'includes/class-efc-counter.php';
require_once EFC_PRO_PLUGIN_DIR . 'includes/class-efc-admin.php';

add_action( 'wp_enqueue_scripts', function() {
    wp_register_script( 'efc-pro-frontend', EFC_PRO_PLUGIN_URL . 'assets/js/efc-pro-frontend.js', array('jquery'), EFC_PRO_VERSION, true );
    wp_register_style( 'efc-pro-style', EFC_PRO_PLUGIN_URL . 'assets/css/efc-pro-style.css', array(), EFC_PRO_VERSION );
    wp_localize_script( 'efc-pro-frontend', 'EFCPro_Ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('efc_pro_nonce')
    ));
    wp_enqueue_script( 'efc-pro-frontend' );
    wp_enqueue_style( 'efc-pro-style' );
});

add_action( 'wp_ajax_efc_pro_get_count', function() {
    check_ajax_referer( 'efc_pro_nonce', 'nonce' );
    $slug = isset($_POST['slug']) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
    if ( empty( $slug ) ) { wp_send_json_error( array('message'=>'Missing slug'), 400 ); }
    $count = (int) get_option( \EFC\Counter::opt_count_key( $slug ), 0 );
    wp_send_json_success( array('count'=>$count) );
});

add_action( 'wp_ajax_nopriv_efc_pro_get_count', function() {
    check_ajax_referer( 'efc_pro_nonce', 'nonce' );
    $slug = isset($_POST['slug']) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
    if ( empty( $slug ) ) { wp_send_json_error( array('message'=>'Missing slug'), 400 ); }
    $count = (int) get_option( \EFC\Counter::opt_count_key( $slug ), 0 );
    wp_send_json_success( array('count'=>$count) );
});

add_shortcode( 'form_counter', function( $atts ) {
    $atts = shortcode_atts( array(
        'form'       => 'Waitlist',
        'type'       => 'default',
        'target'     => '',
        'style'      => '',
        'font_size'  => '',
        'font_color' => '',
        'font_family'=> ''
    ), $atts, 'form_counter' );

    $form_name = sanitize_text_field( $atts['form'] );
    $slug = \EFC\Counter::slug_from_form_name( $form_name );
    $count = (int) get_option( \EFC\Counter::opt_count_key( $slug ), 0 );
    $target = is_numeric($atts['target']) ? intval($atts['target']) : '';

    $settings = get_option('efc_pro_settings', array());

    // Use shortcode override or fallback to admin defaults
    $style      = $atts['style']      ?: ($settings['default_template'] ?? 'minimal');
    $font_size  = $atts['font_size']  ?: ($settings['default_font_size'] ?? '');
    $font_color = $atts['font_color'] ?: ($settings['default_font_color'] ?? '');
    $font_family= $atts['font_family']?: ($settings['default_font_family'] ?? '');

    $inline_style = '';
    if($font_size)   $inline_style .= 'font-size:' . esc_attr($font_size) . ';';
    if($font_color)  $inline_style .= 'color:' . esc_attr($font_color) . ';';
    if($font_family) $inline_style .= 'font-family:' . esc_attr($font_family) . ';';

    $data = sprintf(
        'data-efc-slug="%s" data-efc-type="%s" data-efc-target="%s" data-efc-style="%s"',
        esc_attr($slug), esc_attr($atts['type']), esc_attr($target), esc_attr($style)
    );

    $template_class = 'efc-pro-' . esc_attr($style);

    if ($atts['type'] === 'progress' && $target) {
        $percent = $target>0 ? round(min(100,($count/$target)*100),2) : 0;
        return sprintf(
            '<div class="efc-pro-progress %s" %s><div class="efc-pro-bar"><div class="efc-pro-fill" style="%s"></div></div><div class="efc-pro-label">%s / %s</div></div>',
            esc_attr($template_class),
            $data,
            $inline_style,
            number_format_i18n($count),
            number_format_i18n($target)
        );
    }

    return sprintf(
        '<span class="efc-pro-counter %s" %s style="%s">%d</span>',
        esc_attr($template_class),
        $data,
        $inline_style,
        $count
    );
});
