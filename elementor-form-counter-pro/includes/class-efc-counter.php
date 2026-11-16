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

    private static function email_fingerprint( $email ) {
        return hash_hmac( 'sha256', strtolower( trim( $email ) ), wp_salt( 'auth' ) );
    }

    private static function acquire_form_lock( $slug ) {
        $key = 'efc_pro_lock_' . $slug;
        $deadline = microtime( true ) + 4;
        do {
            if ( add_option( $key, time(), '', false ) ) {
                return $key;
            }
            $created = (int) get_option( $key, 0 );
            if ( $created && $created < time() - 15 ) {
                delete_option( $key );
            }
            usleep( 100000 );
        } while ( microtime( true ) < $deadline );
        return false;
    }

    public static function on_form_submit( $record, $handler ) {
        $form_name = $record->get_form_settings( 'form_name' );
        if ( empty( $form_name ) ) return;
        $slug = self::slug_from_form_name( $form_name );
        $raw_fields = $record->get( 'fields' );
        if ( empty( $raw_fields ) || ! is_array( $raw_fields ) ) return;

        $email = '';
        foreach ( $raw_fields as $field ) {
            if ( isset( $field['type'], $field['value'] ) && 'email' === $field['type'] ) {
                $email = sanitize_email( $field['value'] );
                if ( $email ) break;
            }
        }
        if ( ! $email && isset( $raw_fields['email']['value'] ) ) {
            $email = sanitize_email( $raw_fields['email']['value'] );
        }
        if ( ! $email || ! is_email( $email ) ) return;

        $lock = self::acquire_form_lock( $slug );
        if ( ! $lock ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'EFC: Could not acquire submission lock for form ' . $slug );
            }
            return;
        }

        try {
            $emails_key = self::opt_emails_key( $slug );
            $count_key = self::opt_count_key( $slug );
            $emails = get_option( $emails_key, array() );
            if ( ! is_array( $emails ) ) $emails = array();

            // Migrate legacy plaintext entries to keyed fingerprints on first write.
            $fingerprints = array();
            foreach ( $emails as $stored ) {
                $stored = (string) $stored;
                $fingerprints[] = is_email( $stored ) ? self::email_fingerprint( $stored ) : $stored;
            }
            $fingerprint = self::email_fingerprint( $email );
            $is_new = ! in_array( $fingerprint, $fingerprints, true );

            if ( $is_new ) {
                $fingerprints[] = $fingerprint;
                update_option( $emails_key, array_values( array_unique( $fingerprints ) ), false );
                $count = (int) get_option( $count_key, 0 ) + 1;
                update_option( $count_key, $count, false );

                $settings = get_option( 'efc_pro_settings', array() );
                $target = isset( $settings['milestone_target_' . $slug ] ) ? absint( $settings['milestone_target_' . $slug ] ) : 0;
                if ( $target > 0 && $count >= $target ) {
                    $webhook = isset( $settings['webhook_url_' . $slug ] ) ? esc_url_raw( $settings['webhook_url_' . $slug ] ) : '';
                    if ( $webhook && wp_http_validate_url( $webhook ) ) {
                        wp_remote_post( $webhook, array(
                            'body' => wp_json_encode( array( 'form' => sanitize_text_field( $form_name ), 'count' => $count ) ),
                            'headers' => array( 'Content-Type' => 'application/json' ),
                            'timeout' => 5,
                            'redirection' => 0,
                        ) );
                    }
                    if ( ! empty( $settings['milestone_email_' . $slug ] ) ) {
                        wp_mail( get_option( 'admin_email' ), 'Form milestone reached: ' . sanitize_text_field( $form_name ), 'Your form reached its configured submission target.' );
                    }
                }
            } else {
                // Ensure legacy values are not retained after a repeat submission.
                if ( $fingerprints !== $emails ) {
                    update_option( $emails_key, array_values( array_unique( $fingerprints ) ), false );
                }
            }

            $forms_index = get_option( self::opt_forms_index_key(), array() );
            if ( ! is_array( $forms_index ) ) $forms_index = array();
            if ( ! in_array( $slug, $forms_index, true ) ) {
                $forms_index[] = $slug;
                update_option( self::opt_forms_index_key(), $forms_index, false );
                update_option( 'efc_pro_label_' . $slug, sanitize_text_field( $form_name ), false );
            }
        } finally {
            delete_option( $lock );
        }
    }
}
Counter::init();
