<?php
/**
 * Ouun Consent Settings
 * Forked from humanmade/altis-consent
 *
 * @package ouun/consent
 */

namespace Ouun\Consent\Settings;

use Ouun\Consent\CookiePolicy;
use WP_Post;
use WP_Privacy_Policy_Content;

/**
 * Kick off all the things.
 */
function bootstrap()
{
    add_action('admin_init', __NAMESPACE__ . '\\register_consent_settings');
    add_action('current_screen', __NAMESPACE__ . '\\redirect_native_policy_page');
    add_action('admin_init', __NAMESPACE__ . '\\update_privacy_policy_page');
    add_action('admin_init', __NAMESPACE__ . '\\create_policy_page', 9);
    add_action('admin_menu', __NAMESPACE__ . '\\add_ouun_privacy_page');
    add_action('admin_menu', __NAMESPACE__ . '\\remove_core_privacy_page');
    add_filter('wp_consent_api_cookie_expiration', __NAMESPACE__ . '\\register_cookie_expiration');
    add_filter(
        'admin_body_class',
        function ($body_class) {
            if (is_ouun_privacy_page()) {
                $body_class .= ' privacy-settings ';
            }

            return $body_class;
        }
    );
}

/**
 * Check if current page is Privacy Page
 *
 * @param string $page
 * @return bool
 */
function is_ouun_privacy_page(string $page = '') : bool
{
    $current_screen = get_current_screen();

    if (!empty($page)) {
        return isset($current_screen->base) && $current_screen->base === $page;
    }

    return isset($current_screen->base) && strpos($current_screen->base, 'settings_page_ouun_privacy') === 0;
}

/**
 * Remove the WordPress core Privacy page from the Settings menu.
 */
function remove_core_privacy_page()
{
    global $submenu;

    unset($submenu['options-general.php'][45]);
}

/**
 * Filter the consent API cookie expiration to what was saved in the settings.
 */
function register_cookie_expiration()
{
    return get_consent_option('cookie_expiration', 30);
}

function redirect_native_policy_page()
{
    if (is_admin()) {
        $current_screen = get_current_screen();

        if (isset($current_screen->base) && 'options-privacy' === $current_screen->base && ! isset($_GET['tab'])) {
            wp_redirect(admin_url().'options-general.php?page=ouun_privacy');
            exit();
        }
    }
}

/**
 * Update the Privacy Policy Page setting.
 *
 * Handles updating the privacy page option.
 * Since this page and setting is not registered like a normal WordPress option, we need to intercept the $_POST data if the privacy policy page setting was updated, and update it manually.
 */
function update_privacy_policy_page()
{
    if (
        // Validate the nonce.
        ! isset($_POST['_ouun_privacy_policy_page_nonce']) ||
        ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ouun_privacy_policy_page_nonce'])), 'ouun.privacy_policy_page') ||
        // Bail early if we're not on the consent page.
        ! isset($_POST['option_page']) ||
        'cookie_consent_options' !== sanitize_text_field(wp_unslash($_POST['option_page']))
    ) {
        return;
    }

    $privacy_option         = 'wp_page_for_privacy_policy';
    $privacy_policy_page_id = (int) get_option($privacy_option);
    $updated_id             = ! empty($_POST[ $privacy_option ]) ? sanitize_text_field(wp_unslash($_POST[ $privacy_option ])) : false;

    if (absint($updated_id) === $privacy_policy_page_id) {
        return;
    }

    update_option($privacy_option, $updated_id);
}

/**
 * Auto-create the policy page based on the button that was clicked.
 *
 * @return void
 */
function create_policy_page()
{
    if (
        // Validate the nonce.
        ! isset($_POST['_ouun_privacy_policy_page_nonce']) ||
        ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ouun_privacy_policy_page_nonce'])), 'ouun.privacy_policy_page') ||
        // Make sure we've requested a new policy page.
        ! isset($_POST['create_policy_page']) ||
        // Make sure that the request was valid.
        ! in_array(sanitize_text_field(wp_unslash($_POST['create_policy_page'])), get_allowed_policy_page_values(), true)
    ) {
        return;
    }

    $policy_page = sanitize_text_field(wp_unslash($_POST['create_policy_page']));

    /**
     * Whether we are using the block editor.
     *
     * This defaults to true, but if false, we omit the gutenberg block support in the policy content.
     *
     * @var bool True/false whether the site is using the block editor.
     */
    $block_editor = apply_filters('ouun.consent.use_block_editor', '__return_true');

    if ($policy_page === 'privacy_policy') {
        if (! class_exists('WP_Privacy_Policy_Content')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';
        }

        $option_name         = 'wp_page_for_privacy_policy';
        $policy_page_title   = __('Privacy Policy', 'ouun-consent');
        $policy_page_content = WP_Privacy_Policy_Content::get_default_content($block_editor);
    } elseif ($policy_page === 'cookie_policy') {
        $option_name         = 'cookie_consent_options';
        $policy_page_title   = __('Cookie Policy', 'ouun-consent');
        $policy_page_content = CookiePolicy\get_default_content($block_editor);
    }

    $policy_page_id = wp_insert_post([
        'post_title'   => $policy_page_title,
        'post_status'  => 'draft',
        'post_type'    => 'page',
        'post_content' => $policy_page_content,
    ], true);

    if ($policy_page === 'privacy_policy') {
        $option_value = $policy_page_id;
    } elseif ($policy_page === 'cookie_policy') {
        $option_value = get_consent_option();

        $option_value['policy_page'] = $policy_page_id;
    }

    if (is_wp_error($policy_page_id)) {
        add_settings_error(
            "page_for_$policy_page",
            "page_for_$policy_page",
            // Translators: %s is the name of the page we're trying to create.
            sprintf(__('Unable to create a %s page', 'ouun-consent'), $policy_page_title),
            'error'
        );
    } else {
        update_option($option_name, $option_value);
    }

    wp_redirect(admin_url("post.php?post=$policy_page_id&action=edit"));
    exit;
}

/**
 * Register the Ouun Privacy submenu page.
 */
function add_ouun_privacy_page()
{
    add_options_page(
        __('Privacy Settings', 'ouun-consent'),
        __('Privacy', 'ouun-consent'),
        'manage_options',
        'ouun_privacy',
        __NAMESPACE__ . '\\render_ouun_privacy_page'
    );
}

/**
 * Return an array of Ouun Consent settings.
 */
function get_cookie_consent_settings_fields()
{
    $fields = [
        'display_banner' => [
            'id'       => 'display_banner',
            'title'    => __('Display Cookie Consent Banner', 'ouun-consent'),
            'callback' => __NAMESPACE__ . '\\render_display_banner',
        ],
        'cookie_expiration' => [
            'id'       => 'cookie_expiration',
            'title'    => __('Cookie Expiration', 'ouun-consent'),
            'callback' => __NAMESPACE__ . '\\cookie_expiration',
        ],
        'banner_options' => [
            'id'       => 'banner_options',
            'title'    => __('Consent Banner Options', 'ouun-consent'),
            'callback' => __NAMESPACE__ . '\\render_banner_options',
        ],
        'banner_text' => [
            'id'       => 'banner_text',
            'title'    => __('Banner Message', 'ouun-consent'),
            'callback' => __NAMESPACE__ . '\\render_banner_message',
        ],
        'cookie_policy_page' => [
            'id'       => 'cookie_policy_page',
            'title'    => __('Cookie Policy Page', 'ouun-consent'),
            'callback' => __NAMESPACE__ . '\\render_cookie_policy_page',
        ],
    ];

    /**
     * Allow these fields to be filtered. New options fields can be added in the above format.
     *
     * @var array $fields An array of settings fields with unique IDs, titles and callback functions.
     */
    return apply_filters('ouun.consent.consent_settings_fields', $fields);
}

/**
 * Return an array of cookie banner options.
 */
function get_cookie_banner_options()
{
    $options = [
        [
            'value' => 'none',
            'label' => __('Allow/Deny All Cookies', 'ouun-consent'),
        ],
        [
            'value' => 'all-categories',
            'label' => __('All Cookie Categories', 'ouun-consent'),
        ],
    ];

    return apply_filters('ouun.consent.banner_options', $options);
}

/**
 * Register the Ouun Consent settings.
 */
function register_consent_settings()
{
    $page    = 'ouun_privacy';
    $section = 'cookie_consent';

    register_setting('cookie_consent_options', 'cookie_consent_options', __NAMESPACE__ . '\\validate_privacy_options');

    add_settings_section(
        'privacy_policy',
        __('Privacy Policy', 'ouun-consent'),
        __NAMESPACE__ . '\\ouun_privacy_section',
        $page
    );

    // Pull in the privacy policy page settings.
    privacy_policy_page_settings();

    add_settings_section(
        $section,                                  // New settings section.
        __('Cookie Consent', 'ouun-consent'),   // Section title.
        __NAMESPACE__ . '\\ouun_consent_section', // Callback function.
        $page                                      // Settings Page.
    );

    // Get the Ouun Consent settings and loop through them, registering each.
    $fields = get_cookie_consent_settings_fields();
    foreach ($fields as $field) {
        add_settings_field($field['id'], $field['title'], $field['callback'], $page, $section);
    }
}

/**
 * Handle the Privacy Policy page settings.
 *
 * Much of this code is copy pasta from the WordPress core Privacy page.
 */
function privacy_policy_page_settings()
{
    // If a Privacy Policy page ID is available, make sure the page actually exists. If not, display an error.
    $privacy_policy_page_exists = false;
    $privacy_policy_page_id     = (int) get_option('wp_page_for_privacy_policy');

    if (! empty($privacy_policy_page_id)) {
        $privacy_policy_page = get_post($privacy_policy_page_id);

        if (! $privacy_policy_page instanceof WP_Post) {
            add_settings_error(
                'page_for_privacy_policy',
                'page_for_privacy_policy',
                __('The currently selected Privacy Policy page does not exist. Please create or select a new page.'),
                'error'
            );
        } else {
            if ('trash' === $privacy_policy_page->post_status) {
                add_settings_error(
                    'page_for_privacy_policy',
                    'page_for_privacy_policy',
                    sprintf(
                        /* translators: %s: URL to Pages Trash. */
                        __('The currently selected Privacy Policy page is in the Trash. Please create or select a new Privacy Policy page or <a href="%s">restore the current page</a>.'),
                        'edit.php?post_status=trash&post_type=page'
                    ),
                    'error'
                );
            } else {
                $privacy_policy_page_exists = true;
            }
        }
    }

    $label = $privacy_policy_page_exists ? __('Update your Privacy Policy page', 'ouun-consent') : __('Select a Privacy Policy page', 'ouun-consent');

    add_settings_field('wp_page_for_privacy_policy', $label, __NAMESPACE__ . '\\render_privacy_policy_page_setting', 'ouun_privacy', 'privacy_policy');
}

/**
 * Return the Privacy Policy admin text.
 *
 * This text is copy pasta from WordPress core's Privacy page, but can be filtered to say whatever you want, or removed entirely.
 */
function get_privacy_policy_text()
{
    ob_start();
    ?>
    <p>
        <?php esc_html_e('As a website owner, you may need to follow national or international privacy laws. For example, you may need to create and display a Privacy Policy.', 'ouun-consent'); ?>
        <?php esc_html_e('If you already have a Privacy Policy page, please select it below. If not, please create one.', 'ouun-consent'); ?>
    </p>
    <p>
        <?php esc_html_e('The new page will include help and suggestions for your Privacy Policy.', 'ouun-consent'); ?>
        <?php esc_html_e('However, it is your responsibility to use those resources correctly, to provide the information that your Privacy Policy requires, and to keep that information current and accurate.', 'ouun-consent'); ?>
    </p>
    <p>
        <?php esc_html_e('After your Privacy Policy page is set, we suggest that you edit it.', 'ouun-consent'); ?>
        <?php esc_html_e('We would also suggest reviewing your Privacy Policy from time to time, especially after installing or updating any themes or plugins. There may be changes or new suggested information for you to consider adding to your policy.', 'ouun-consent'); ?>
    </p>
    <?php

    $privacy_message = ob_get_clean();

    /**
     * Allow the privacy policy message to be filtered or removed.
     *
     * @var string $privacy_message The message to display above the Privacy Policy page setting.
     */
    return apply_filters('ouun.consent.privacy_policy_message', $privacy_message);
}

/**
 * Validation function that I need to flesh out still.
 *
 * @param array $dirty An array of unvalidated option data to validate.
 */
function validate_privacy_options($dirty)
{
    $validated = [];

    // Make sure cookie_expiration is a number.
    $validated['cookie_expiration'] = is_numeric($dirty['cookie_expiration']) ? $dirty['cookie_expiration'] : '30';

    // Make sure the banner_options is in the array of options we expect.
    $validated['banner_options'] = in_array($dirty['banner_options'], wp_list_pluck(get_cookie_banner_options(), 'value'), true) ? $dirty['banner_options'] : 'none';

    // Strip evil scripts from the message.
    $validated['banner_message'] = wp_kses_post($dirty['banner_message']);

    // Make sure the page exists.
    $page_exists = (bool) get_post(absint($dirty['policy_page']));
    $validated['policy_page'] = $page_exists ? $dirty['policy_page'] : '';

    $validated['display_banner'] = is_numeric($dirty['display_banner']) ? (int) $dirty['display_banner'] : 1;

    /**
     * Allow the validated data to be filtered.
     * This is useful if additional settings are added to the page that need to be validated.
     *
     * @var array $validated An array of validated data.
     * @var array $dirty     An array of unvalidated data.
     */
    return apply_filters('ouun.consent.validate_privacy_options', $validated, $dirty);
}

/**
 * Render the Ouun Privacy page header.
 */
function render_ouun_privacy_page_header()
{
    $active_class = 'class="privacy-settings-tab active" aria-current="true"';
    $class = 'class="privacy-settings-tab"';
    ?>
        <div class="privacy-settings-header">
            <div class="privacy-settings-title-section">
                <h1>
                    <?php _e('Privacy'); ?>
                </h1>
            </div>

            <nav class="privacy-settings-tabs-wrapper" aria-label="<?php esc_attr_e('Secondary menu'); ?>">
                <a href="<?php echo esc_url(admin_url('options-privacy.php')); ?>" <?php echo is_ouun_privacy_page('settings_page_ouun_privacy') ? $active_class : $class; ?>>
                    <?php
                    /* translators: Tab heading for Site Health Status page. */
                    _ex('Settings', 'Privacy Settings');
                    ?>
                </a>

                <?php do_action('ouun.consent.settings_header_tabs'); ?>
            </nav>
        </div>

        <hr class="wp-header-end">
    <?php
}

/**
 * Render the Ouun Privacy page.
 */
function render_ouun_privacy_page()
{
    render_ouun_privacy_page_header();
    ?>
    <div class="privacy-settings-body hide-if-no-js">
        <div class="wrap">
            <form action="options.php" method="post">
                <?php

                settings_fields('cookie_consent_options');
                do_settings_sections('ouun_privacy');
                ?>
                <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save Privacy Settings', 'ouun-consent'); ?>" />
            </form>
        </div>
    </div>

    <?php
}

/**
 * The Cookie Consent section. Nothing is here  yet but it could contain a notice or information about cookie consent policies.
 */
function ouun_consent_section()
{
}

/**
 * The Privacy Policy page section.
 * This just adds a nonce that we can validate against when we intercept the $_POST data to update the privacy policy page setting.
 */
function ouun_privacy_section()
{
    $nonce = wp_create_nonce('ouun.privacy_policy_page');
    echo wp_kses_post(get_privacy_policy_text());
	echo '<input type="hidden" name="_ouun_privacy_policy_page_nonce" value="' . sanitize_text_field( $nonce ) . '" />'; // phpcs:ignore
}

/**
 * Get a specific consent option, if one exists. If no option value is passed, all saved cookie_consent_options will be returned.
 *
 * @param mixed $option  The option value to get from the options table.
 * @param mixed $default The default value for the given option.
 *
 * @return mixed         The value for the requested option, or all cookie_consent_options if nothing was passed.
 */
function get_consent_option($option = '', $default = '')
{
    $options = get_option('cookie_consent_options');

    if (empty($option)) {
        return $options;
    }

    if (! isset($options[ $option ])) {
        return $default;
    }

    return $options[ $option ];
}

/**
 * Render the display cookie consent banner setting.
 */
function render_display_banner()
{
    $display_banner = get_consent_option('display_banner', 1);

    ?>
    <select name="cookie_consent_options[display_banner]" id="display_banner" value="<?php echo absint($display_banner); ?>">
        <option value="0" <?php selected($display_banner, 0); ?>>
            <?php esc_html_e('Do not display banner', 'ouun-consent'); ?>
        </option>
        <option value="1" <?php selected($display_banner, 1); ?>>
            <?php esc_html_e('Display consent banner', 'ouun-consent'); ?>
        </option>
    </select>
    <?php
}

/**
 * Render the cookie expiration setting.
 */
function cookie_expiration()
{
    $expiration = get_consent_option('cookie_expiration', 90);
    ?>
    <input id="cookie_consent_expiration" name="cookie_consent_options[cookie_expiration]" type="number" value="<?php echo absint($expiration); ?>" class="small-text" step="1" />
    <p class="description">
        <?php esc_html_e('How long, in days, cookies should be stored.', 'ouun-consent'); ?>
    </p>
    <?php
}

/**
 * Render the consent banner options setting.
 */
function render_banner_options()
{
    $selected       = get_consent_option('banner_options', 'none');
    $banner_options = get_cookie_banner_options();
    ?>
    <select name="cookie_consent_options[banner_options]" id="banner_options" value="<?php echo esc_attr($selected); ?>">
        <?php
        // Loop through each of the options and render the html.
        foreach ($banner_options as $option) {
            ?>
            <option value="<?php echo esc_attr($option['value']); ?>" <?php selected($selected, $option['value']); ?>><?php echo esc_html($option['label']); ?></option>
        <?php } ?>
    </select>
    <p class="description">
        <?php esc_html_e('If you would like to display an option to configure each cookie category in the consent banner, select All Cookie Categories.', 'ouun-consent'); ?><br />
        <?php esc_html_e('If you would like to only display an option to accept or deny all non-functional cookies, select Allow/Deny All Cookies.', 'ouun-consent'); ?>
    </p>
    <?php
}

/**
 * Return the default banner message.
 *
 * @return string The default banner message.
 */
function get_default_banner_message() : string
{
    /**
     * Allow the default cookie banner message to be filtered.
     *
     * @var string $default_message The default cookie consent banner message.
     */
    return apply_filters('ouun.consent.default_banner_message', esc_html__('This site uses cookies to provide a better user experience.', 'ouun-consent'));
}

/**
 * Render the banner message setting.
 */
function render_banner_message()
{
    $message = get_consent_option('banner_message', get_default_banner_message());

    // Render a TinyMCE editor for the banner message.
    wp_editor(wp_kses_post($message), 'banner_message', [
        'textarea_name' => 'cookie_consent_options[banner_message]',
        'teeny'         => true,
        'textarea_rows' => 5,
    ]);
}

/**
 * Get the filterable array of policy page values that can be created.
 *
 * @return array An array of policy page types.
 */
function get_allowed_policy_page_values() : array
{
    /**
     * Filter the array of allowed policy pages we can create.
     *
     * This is used by the render_secondary_button function. The default pages that can be created are privacy_policy and cookie_policy.
     *
     * @var array An array of allowed policy page values.
     */
    return apply_filters('ouun.consent.allowed_policy_page_values', [ 'cookie_policy', 'privacy_policy' ]);
}

/**
 * Display a secondary button.
 *
 * Used to create the Create Policy Page buttons, but can be filtered and used for other things.
 *
 * @param string $button_text The text to display in the button.
 * @param string $value       The button value. On the settings page, this is used to determine the type of policy page the buttons create.
 * @param string $name        The html name of the button we're creating. Defaults to 'create_policy_page'.
 * @param string $type        The html button type. The default value is 'submit', and valid values are 'submit', 'reset', and 'button'. Invalid values revert to 'submit'.
 */
function render_secondary_button(string $button_text, string $value = 'privacy_policy', string $name = 'create_policy_page', string $type = 'submit')
{
    // Make sure the button type is valid. Invalid types are reset to 'submit'.
    $type = in_array($type, [ 'submit', 'reset', 'button' ], true) ? $type : 'submit';

    // Make sure the value passed is valid. Invalid values default to "privacy_policy".
    $value = in_array($value, get_allowed_policy_page_values(), true) ? $value : 'privacy_policy';

    ?>
    <button name="<?php echo esc_attr($name); ?>" type="<?php echo esc_attr($type); ?>" value="<?php echo esc_attr($value); ?>" class="button button-secondary"><?php echo esc_html($button_text); ?></button>
    <?php
}

/**
 * Render the cookie policy page setting.
 */
function render_cookie_policy_page()
{
    $page_id = get_consent_option('policy_page', 0);

    // If there are pages, display the page dropdown mehu. Otherwise, display a message stating that there are no pages.
    if (pages_exist()) {
        wp_dropdown_pages([
            'id'                => 'policy_page',
            'name'              => 'cookie_consent_options[policy_page]',
            'show_option_none'  => '&mdash; ' . esc_html__('Select an option', 'ouun-consent') . ' &mdash;',
            'option_none_value' => '0',
            'selected'          => esc_attr($page_id),
            'post_status'       => [ 'draft', 'publish' ],
        ]);
    } else {
        esc_html_e('There are no pages.', 'ouun-consent');
    }

    render_secondary_button(__('Create Cookie Policy Page', 'ouun-consent'), 'cookie_policy');
}

/**
 * Render the privacy policy page setting.
 */
function render_privacy_policy_page_setting()
{
    $privacy_policy_page_id = (int) get_option('wp_page_for_privacy_policy');

    if (pages_exist()) {
        wp_dropdown_pages([
            'name'              => 'wp_page_for_privacy_policy',
            'show_option_none'  => '&mdash; ' . esc_html__('Select an option', 'ouun-consent') . ' &mdash;',
            'option_none_value' => '0',
			'selected'          => $privacy_policy_page_id, // phpcs:ignore
            'post_status'       => [ 'draft', 'publish' ],
        ]);
    } else {
        esc_html_e('There are no pages.', 'ouun-consent');
    }

    render_secondary_button(__('Create Privacy Policy Page', 'ouun-consent'));
}

/**
 * Check if pages exist.
 *
 * @return bool True if pages exist, false if no pages exist. Checks if there are published or draft pages.
 */
function pages_exist() : bool
{
    $has_pages = wp_cache_get('ouun.privacy.has_pages', 'ouun');

    if (! $has_pages) {
        $has_pages = (bool) get_posts([
            'post_type'      => 'page',
            'posts_per_page' => 1,
            'post_status'    => [ 'publish', 'draft' ],
        ]);

        // Cache the result so we don't need to run another get_posts later.
        wp_cache_set('ouun.privacy.has_pages', $has_pages, 'ouun');
    }

    return $has_pages;
}
