<?php
/**
 * Plugin Name: Ouun Consent
 * Plugin URI: https://github.com/ouun/ouun-consent/
 * Description: Hooks into the Consent API to provide basic settings and a cookie consent banner.
 * Version: 1.0.6
 * Requires PHP: 7.1
 * Text Domain: ouun-consent
 * Domain Path: /languages
 * GitHub Plugin URI: ouun/ouun-consent
 * Author: ouun.io
 * Author URI: https://ouun.io
 */

namespace Ouun\Consent;

require_once __DIR__ . '/inc/cookie-policy.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/settings.php';

bootstrap();
Settings\bootstrap();
