<?php

defined( 'ABSPATH' ) || exit;

/** Server-side validation after generation. Input policy alone is not sufficient. */
final class SCHA_Output_Policy {
    public static function validate( string $answer ): array {
        $plain = trim( wp_strip_all_tags( $answer ) );
        if ( '' === $plain ) return array( 'valid' => false, 'category' => 'empty-output' );

        $forbidden = array(
            'clinical_directive' => '/\b(you have|your diagnosis is|take|use|start|stop|i recommend|i suggest|you should take|the remedy for you is)\b.{0,70}\b(remedy|medicine|homeopathic medicine|30c|200c|1m|cm|dose|drops|daily|times a day)\b/iu',
            'urdu_clinical'      => '/(آپ کو .{0,30}(بیماری|تشخیص)|آپ یہ دوا لیں|یہ دوا لیں|میں (?:یہ )?دوا تجویز|آپ کے لیے .{0,25}(دوا|ریمیڈی)|پوٹنسی|خوراک|قطرے|دن میں .{0,15}بار)/u',
            'arabic_clinical'    => '/(تشخيصك هو|خذ هذا الدواء|أنصحك ب.{0,30}(دواء|علاج)|الدواء المناسب لك|الجرعة|القوة الدوائية|مرات يوميا)/u',
            'secret_leak'        => '/\b(api[_ -]?key|system prompt|developer message|password|secret token)\b\s*[:=]/iu',
        );
        foreach ( $forbidden as $category => $pattern ) {
            if ( preg_match( $pattern, $plain ) ) return array( 'valid' => false, 'category' => $category );
        }
        return array( 'valid' => true, 'category' => 'educational' );
    }
}
