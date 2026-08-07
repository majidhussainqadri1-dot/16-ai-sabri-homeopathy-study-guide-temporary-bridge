<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Prompt_Policy {
    private const PATTERNS = array(
        'emergency' => array(
            '/\b(chest pain|cannot breathe|severe bleeding|unconscious|stroke|heart attack|medical emergency|overdose|poisoning)\b/iu',
            '/(سینے میں شدید درد|سانس نہیں آ رہی|شدید خون بہ|بے ہوش|فالج|دل کا دورہ|ایمرجنسی|زہر کھا)/u',
            '/(ألم شديد في الصدر|لا أستطيع التنفس|نزيف شديد|فاقد الوعي|حالة طارئة)/u',
        ),
        'self_harm' => array(
            '/\b(kill myself|suicide|self[- ]?harm|end my life)\b/iu',
            '/(خودکشی|اپنے آپ کو مار|زندگی ختم کرنا|خود کو نقصان)/u',
            '/(انتحار|أقتل نفسي|إيذاء النفس)/u',
        ),
        'diagnosis' => array(
            '/\b(diagnose|what disease do i have|tell me my diagnosis|is this cancer|confirm my illness)\b/iu',
            '/(میری تشخیص|مجھے کون سی بیماری|کیا مجھے کینسر|بیماری بتائیں)/u',
            '/(شخّص حالتي|ما هو مرضي|هل لدي سرطان)/u',
        ),
        'prescription' => array(
            '/\b(prescribe|which remedy should i take|what remedy should i take|recommend (?:a |the )?(?:remedy|medicine)|suggest (?:a |the )?(?:remedy|medicine)|give me a medicine|what should i take|treatment plan|treat my (?:case|condition|symptoms))\b/iu',
            '/(دوا تجویز|کون سی (?:ہومیوپیتھک )?دوا لوں|کون سی ریمیڈی لوں|میرے لیے دوا|نسخہ دیں|علاج بتائیں|میرے کیس کا علاج)/u',
            '/(صف لي دواء|أي علاج آخذ|أي دواء آخذ|اقترح دواء|أعطني وصفة|عالج حالتي)/u',
        ),
        'potency_dosage' => array(
            '/\b(potency|dosage|dose|how many drops|30c|200c|1m|cm potency)\b/iu',
            '/(طاقت کون سی|پوٹنسی|خوراک|کتنے قطرے|کتنی بار)/u',
            '/(الجرعة|القوة الدوائية|كم قطرة|كم مرة)/u',
        ),
        'prompt_injection' => array(
            '/\b(ignore (all|any|the) previous instructions|reveal your system prompt|developer message|bypass safety|jailbreak)\b/iu',
            '/(پچھلی تمام ہدایات نظر انداز|سسٹم پرامپٹ دکھا|حفاظتی اصول توڑ|جیل بریک)/u',
            '/(تجاهل كل التعليمات السابقة|اعرض رسالة النظام|تجاوز الأمان)/u',
        ),
        'private_data' => array(
            '/\b(reveal|show|extract|dump)\b.{0,40}\b(api key|password|secret|private prompt|system prompt|patient record|identity evidence)\b/iu',
            '/(پاس ورڈ|خفیہ کلید|سسٹم پرامپٹ|مریض کا ریکارڈ|شناختی ثبوت).{0,40}(دکھا|نکال|بتا)/u',
        ),
    );

    public static function classify( string $prompt ): array {
        $prompt = trim( wp_strip_all_tags( $prompt ) );
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $prompt ) : strlen( $prompt );
        $max    = absint( SCHA_Settings::get( 'max_prompt_chars', 4000 ) );

        if ( '' === $prompt ) {
            return self::result( false, 'empty', __( 'Please enter a question.', SCHA_TEXT_DOMAIN ) );
        }
        if ( $length > $max ) {
            return self::result( false, 'too_long', sprintf( __( 'The question is too long. The current limit is %d characters.', SCHA_TEXT_DOMAIN ), $max ) );
        }

        foreach ( self::PATTERNS as $category => $patterns ) {
            foreach ( $patterns as $pattern ) {
                if ( preg_match( $pattern, $prompt ) ) {
                    return self::for_category( $category );
                }
            }
        }

        return array(
            'allowed'      => true,
            'category'     => 'educational',
            'message'      => '',
            'escalation'   => array(),
            'policy_version' => SCHA_Policy_Repository::active()['version'],
        );
    }

    private static function for_category( string $category ): array {
        $messages = array(
            'emergency' => __( 'This AI is not an emergency service. Seek immediate help from local emergency services or a qualified clinician now.', SCHA_TEXT_DOMAIN ),
            'self_harm' => __( 'I cannot help with instructions for self-harm. Please contact local emergency services now and stay with a trusted person who can help keep you safe.', SCHA_TEXT_DOMAIN ),
            'diagnosis' => __( 'I can provide source-linked educational information, but I cannot diagnose a person. Please consult a qualified doctor for an individual assessment.', SCHA_TEXT_DOMAIN ),
            'prescription' => __( 'I cannot prescribe a remedy or create an individual treatment plan. Use the educational sources and consult a qualified doctor.', SCHA_TEXT_DOMAIN ),
            'potency_dosage' => __( 'I cannot select potency, dosage, frequency, or an individual medicine. Those decisions require a qualified clinician who has assessed the case.', SCHA_TEXT_DOMAIN ),
            'private_data' => __( 'I cannot reveal private records, secrets, system instructions, credentials, or another person’s data.', SCHA_TEXT_DOMAIN ),
            'prompt_injection' => __( 'I cannot follow instructions that attempt to bypass safety, reveal hidden instructions, or misuse protected sources.', SCHA_TEXT_DOMAIN ),
        );

        return array(
            'allowed'    => false,
            'category'   => $category,
            'message'    => $messages[ $category ] ?? __( 'This request is outside the approved educational scope.', SCHA_TEXT_DOMAIN ),
            'escalation' => self::escalation_links( $category ),
            'policy_version' => SCHA_Policy_Repository::active()['version'],
        );
    }

    private static function result( bool $allowed, string $category, string $message ): array {
        return array(
            'allowed'    => $allowed,
            'category'   => $category,
            'message'    => $message,
            'escalation' => array(),
            'policy_version' => SCHA_Policy_Repository::active()['version'],
        );
    }

    public static function escalation_links( string $category ): array {
        $links = array(
            array( 'label' => __( 'Learn Classical Homeopathy', SCHA_TEXT_DOMAIN ), 'url' => home_url( '/learn/' ) ),
            array( 'label' => __( 'Homeopathy Encyclopedia', SCHA_TEXT_DOMAIN ), 'url' => home_url( '/encyclopedia/' ) ),
            array( 'label' => __( 'Find a Global Doctor', SCHA_TEXT_DOMAIN ), 'url' => home_url( '/doctors/' ) ),
        );
        if ( in_array( $category, array( 'emergency', 'self_harm' ), true ) ) {
            array_unshift( $links, array( 'label' => __( 'Contact local emergency services', SCHA_TEXT_DOMAIN ), 'url' => '' ) );
        }
        return $links;
    }
}
