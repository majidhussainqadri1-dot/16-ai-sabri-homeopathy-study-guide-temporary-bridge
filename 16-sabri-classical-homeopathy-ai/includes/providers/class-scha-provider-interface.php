<?php

defined( 'ABSPATH' ) || exit;

interface SCHA_Provider_Interface {
    public function key(): string;
    public function is_available(): bool;
    public function generate( array $request ): array|WP_Error;
}
