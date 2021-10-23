<?php
/**
 * Button row
 *
 * @package Ouun-Consent
 */

use Ouun\Consent\Settings;

/**
 * Get the consent options and set some specific default values.
 */
$options        = get_option('cookie_consent_options', []);
$options        = wp_parse_args($options, [
    'banner_message' => Settings\get_default_banner_message(),
    'policy_page'    => false,
    'banner_options' => 'none',
]);
$banner_message = $options['banner_message'];
$policy_page    = $options['policy_page'];
$all_categories = $options['banner_options'] === 'all-categories';
?>

<div class="button-row">
    <div class="cookie-consent-message">
        <?php echo wp_kses_post($banner_message); ?>
        <?php if ($policy_page) : ?>
            <?php
                /**
                 * Allow the cookie consent policy template path to be overridden, so it can be customized individually. This template displays the link to the cookie policy page, but only if a cookie policy page has been set.
                 *
                 * @var string The path to the cookie consent policy template.
                 */
                $cookie_consent_policy_path = apply_filters('ouun.consent.cookie_consent_policy_template_path', __DIR__ . '/cookie-consent-policy.php');

                load_template($cookie_consent_policy_path);
            ?>
        <?php endif; ?>
    </div>

    <button class="button give-consent">
        <?php echo esc_html(apply_filters('ouun.consent.accept_all_cookies_button_text', __('Accept all cookies', 'ouun-consent'))); ?>
    </button>

    <button class="button revoke-consent">
        <?php echo esc_html(apply_filters('ouun.consent.accept_only_functional_cookies_button_text', __('Accept only functional cookies', 'ouun-consent'))); ?>
    </button>

    <?php if ($all_categories) : ?>
        <button class="button view-preferences">
            <?php echo esc_html(apply_filters('ouun.consent.cookie_preferences_button_text', __('Cookie preferences', 'ouun-consent'))); ?>
        </button>
    <?php endif; ?>
</div>
