<?php
declare(strict_types=1);

/**
 * Fallback entry point when DocumentRoot cannot be set to /public.
 * All requests are safely delegated to /public/index.php.
 */
require __DIR__ . '/public/index.php';

