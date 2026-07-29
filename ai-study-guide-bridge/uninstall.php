<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'sai_settings' );
delete_option( 'sai_version' );
delete_option( 'sai_page_map' );
delete_transient( 'sai_activation_notice' );

// The managed page is preserved to prevent accidental content loss.
