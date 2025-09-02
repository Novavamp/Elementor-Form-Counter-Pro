<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
$forms = get_option( 'efc_pro_forms_index', array() );
if ( is_array( $forms ) ) {
    foreach ( $forms as $slug ) {
        delete_option( 'efc_pro_' . $slug . '_count' );
        delete_option( 'efc_pro_' . $slug . '_emails' );
        delete_option( 'efc_pro_label_' . $slug );
    }
}
delete_option( 'efc_pro_forms_index' );
delete_option( 'efc_pro_settings' );
