<?php

defined( 'ABSPATH' ) || exit;

final class SAI_Privacy {
	public function hooks() {
		add_action( 'admin_init', array( $this, 'policy' ) );
	}

	public function policy() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( 'AI Sabri Homeopathy Study Guide', '<p>The temporary AI Study Guide connects visitors to a Custom GPT hosted by ChatGPT. The ChatGPT service may receive network, account, device, and conversation information under its own terms and privacy policy. Sabri Homeopathy does not store the ChatGPT conversation through this bridge. Visitors should not submit patient names, contact details, medical record numbers, addresses, or other identifying health information.</p>' );
		}
	}
}
