<?php

defined( 'ABSPATH' ) || exit;

final class SAI_Plugin {
	public function run() {
		( new SAI_Frontend() )->hooks();
		( new SAI_Privacy() )->hooks();

		if ( is_admin() ) {
			( new SAI_Admin() )->hooks();
		}
	}
}
