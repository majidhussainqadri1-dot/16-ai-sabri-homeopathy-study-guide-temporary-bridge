<?php

defined( 'ABSPATH' ) || exit;

final class SAI_Provider {
	const MODE_CUSTOM_GPT = 'custom_gpt';
	const MODE_NATIVE_AI  = 'native_ai';

	public static function mode() {
		// This release intentionally keeps Native Sabri AI reserved for a future module.
		return self::MODE_CUSTOM_GPT;
	}

	public static function url() {
		$settings = SAI_Helpers::settings();
		return SAI_Helpers::valid_gpt_url( $settings['custom_gpt_url'] ) ? $settings['custom_gpt_url'] : SAI_Helpers::default_url();
	}

	public static function iframe_enabled() {
		$settings = SAI_Helpers::settings();
		return self::MODE_CUSTOM_GPT === self::mode() && ! empty( $settings['iframe_attempt'] );
	}
}
