<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Four_Plan_Compliance {
    public const GOVERNING_PLANS = array(
        'SSH-PMP-2026-v3.0',
        'Recovered-Directives-v2.1',
        'Continuous-Value-Top20-v1.0',
        'SSH-F16-PLAN-2026-v1.0',
    );

    public static function manifest( array $modules ): array {
        $modules['file-16'] = array(
            'owner'                  => 'File 16',
            'version'                => SCHA_VERSION,
            'schema'                 => SCHA_Database::SCHEMA_VERSION,
            'governing_plans'        => self::GOVERNING_PLANS,
            'single_free_tier'       => true,
            'donor_advantage'        => false,
            'clinical_authority'     => false,
            'canonical_capabilities' => array( 'grounded-assistant', 'ai-teacher', 'provider-governance', 'evaluation' ),
            'owners_consumed'        => array( '00', '01', '05', '06', '12', '15', '19', '20', '21', '22', '23', '24', '25', '26' ),
        );
        return $modules;
    }
}
