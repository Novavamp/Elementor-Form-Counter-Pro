<?php
namespace EFC;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Counter {
    public static function init() {
        add_action( 'elementor_pro/forms/new_record', [ __CLASS__, 'on_form_submit' ], 10, 2 );
    }
    public static function slug_from_form_name( $form_name ) {
        $slug = sanitize_title( $form_name );
        return $slug ?: 'default';
    }
    public static function opt_emails_key( $slug ) { return 'efc_pro_' . $slug . '_emails'; }
    public static function opt_count_key( $slug ) { return 'efc_pro_' . $slug . '_count'; }
    public static function opt_forms_index_key() { return 'efc_pro_forms_index'; }
    public static function on_form_submit( $record, $handler ) {
        $form_name = $record->get_form_settings( 'form_name' );
        if ( empty( $form_name ) ) return;
        $slug = self::slug_from_form_name( $form_name );
        $raw_fields = $record->get( 'fields' );
        if ( empty( $raw_fields ) || ! is_array( $raw_fields ) ) return;
        $email = '';
        foreach ( $raw_fields as $fid => $field ) {
            $type = isset($field['type']) ? $field['type'] : '';
            $value = isset($field['value']) ? $field['value'] : '';
            if ( empty($email) && $type === 'email' ) $email = sanitize_email($value);
        }
        if ( empty($email) && isset($raw_fields['email']['value']) ) $email = sanitize_email($raw_fields['email']['value']);
        if ( empty($email) ) return;
        $emails_key = self::opt_emails_key($slug);
        $count_key = self::opt_count_key($slug);
        $emails = get_option($emails_key, array());
        if ( ! is_array($emails) ) $emails = array();
        $email_norm = strtolower($email);
        if ( ! in_array($email_norm, $emails, true) ) {
            $emails[] = $email_norm;
            update_option($emails_key, $emails, false);
            $count = (int) get_option($count_key, 0);
            update_option($count_key, $count + 1, false);
            // milestone webhook/email
            $settings = get_option('efc_pro_settings', array());
            $target = isset($settings['milestone_target_' . $slug]) ? intval($settings['milestone_target_' . $slug]) : 0;
            if ($target > 0 && ($count + 1) >= $target) {
                if (!empty($settings['webhook_url_' . $slug])) {
                    wp_remote_post($settings['webhook_url_' . $slug], array('body' => json_encode(array('form'=>$form_name,'slug'=>$slug,'count'=>$count+1)),'headers'=>array('Content-Type'=>'application/json'),'timeout'=>5));
                }
                if (!empty($settings['milestone_email_' . $slug])) {
                    wp_mail(get_option('admin_email'), 'Form milestone reached: ' . $form_name, 'Form "' . $form_name . '" reached target: ' . ($count+1));
                }
            }
        }
        $forms_index = get_option(self::opt_forms_index_key(), array());
        if ( ! is_array($forms_index) ) $forms_index = array();
        if ( ! in_array($slug, $forms_index, true) ) {
            $forms_index[] = $slug;
            update_option(self::opt_forms_index_key(), $forms_index, false);
            update_option('efc_pro_label_' . $slug, sanitize_text_field($form_name), false);
        }
    }
}
Counter::init();
