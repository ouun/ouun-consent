<?php

namespace Ouun\Consent\CookieSettings;

use StoutLogic\AcfBuilder\FieldsBuilder;
use function Ouun\Consent\consent_categories;
use function Ouun\Consent\cookie_prefix;
use function Ouun\Consent\CookiePolicy\get_cookie_category_description;
use function Ouun\Consent\Settings\get_consent_option;
use function Ouun\Consent\Settings\is_ouun_privacy_page;
use function Ouun\Consent\Settings\render_ouun_privacy_page_header;

function bootstrap() {
    /**
     * Register ACF Cookies Options Page and Fields
     */
    add_action('acf/init', function() {
        $domain = get_cookie_domain();
        $option_page = acf_add_options_page(array(
            'page_title' 	    => __('Cookies', 'ouun-consent'),
            'menu_slug' 	    => 'ouun_privacy_cookies',
            'capability'	    => 'manage_options',
            'parent_slug'       => 'options-general.php',
            'redirect'		    => false,
            'position'          => 677,
            'update_button'     => __('Update Cookies', 'ouun-consent'),
            'updated_message'   => __('Cookies updated', 'ouun-consent'),
        ));

        $cookies = new FieldsBuilder('cookies', [
            'title' => sprintf( __( 'Cookies on %1$s', 'ouun-consent' ), $domain ),
        ]);

        $cookies->setLocation('options_page', '==', $option_page['menu_slug']);

        foreach (consent_categories() as $category => $label) {
            $cookies
                ->addTab($category, [
                    'label' => $label,
                ])
                ->addTextarea('category_description_' . $category, [
                    'label'         => sprintf( __( '%1$s Cookies Description', 'ouun-consent' ), $label ),
                    'placeholder'   => wp_strip_all_tags(implode(' ', get_cookie_category_description($category))),
                    'required'      => 0,
                    'new_lines'     => 'wpautop',
                    'rows'          => '5',
                ])
                ->addRepeater('category_' . $category, [
                    'label'         => '',
                    'button_label'  => __('Add Cookie', 'ouun-consent'),
                    'layout'        => 'block'
                ])
                    ->addGroup('cookie', [
                        'label'         => '',
                    ])
                    ->addText('name', [
                        'label'         => __('Name', 'ouun-consent'),
                        'placeholder'   => __('Cookie Name', 'ouun-consent'),
                        'required'      => 1,
                        'wrapper'       => [
                            'width'     => '50'
                        ],
                    ])
                    ->addText('domain',
                        [
                            'label'         => __('Domain', 'ouun-consent'),
                            // 'instructions'  => __('The name of the domain to associate the cookie with.', 'ouun-consent'),
                            'placeholder'   => $domain,
                            'required'      => 1,
                            'default_value' => $category === 'functional' ? $domain : '',
                            'wrapper'   => [
                                'width' => '25'
                            ],
                        ])
                    ->addText('expires', [
                        'label'         => __('Expiration', 'ouun-consent'),
                        // 'instructions'  => __('The expiration time at which the cookie expires.', 'ouun-consent'),
                        'placeholder'   => sprintf( __( 'e.g. %1$s days', 'ouun-consent' ), get_consent_option( 'cookie_expiration' ) ),
                        'required'      => 1,
                        'wrapper'   => [
                            'width' => '25'
                        ],
                    ])
                    ->addTextarea('description',
                    [
                        'label'         => __('Description', 'ouun-consent'),
                        'placeholder' => '',
                        'required' => 1,
                        'rows' => '3',
                    ]);
        }

        acf_add_local_field_group($cookies->build());
    });

    /**
     * Overwrite default consent categories description with ACF field value
     */
    add_action('plugins_loaded', function () {
        overwrite_cookies_categories_description();
    });

    /**
     * Register additional Options Page
     * Hooked before ACF Options Page to add header with tabs
     */
    add_action( 'admin_menu', function () {
        add_options_page(
            __( 'Cookies Settings', 'ouun-consent' ),
            __( 'Cookies', 'ouun-consent' ),
            'manage_options',
            'ouun_privacy_cookies',
            __NAMESPACE__ . '\\render_ouun_cookies_page'
        );

        // Remove above added admin menu
        remove_submenu_page('options-general.php', 'ouun_privacy_cookies');
    }, 9);

    /**
     * Remove admin submenu added by ACF
     */
    add_action( 'admin_menu', function () {
        remove_submenu_page('options-general.php', 'ouun_privacy_cookies');
    }, 200);

    /**
     * Set single column layout
     */
    add_action('load-settings_page_ouun_privacy_cookies', function () {
        add_screen_option('layout_columns', array('max'	=> 1, 'default' => 1));
    }, 20);

    /**
     * Add Cookies Tab to Settings Page Header
     */
    add_action('ouun.consent.settings_header_tabs', function () {
        $active_class = 'class="privacy-settings-tab active" aria-current="true"';
        $class = 'class="privacy-settings-tab"';
       ?>
            <a href="<?php echo esc_url( admin_url( 'options-general.php?page=ouun_privacy_cookies' ) ); ?>" <?php echo is_ouun_privacy_page('settings_page_ouun_privacy_cookies') ? $active_class : $class; ?>>
                <?php
                /* translators: Tab heading for Cookies page. */
                _ex( 'Cookies', 'Privacy Settings' );
                ?>
            </a>
        <?php
    });

    /**
     * Remove Screen Options
     */
    add_filter('screen_options_show_screen', function () {
        return !is_ouun_privacy_page();
    });

    /**
     * Prefill Default Cookies
     */
    foreach (consent_categories() as $category => $label) {
        add_filter('acf/load_value/name=category_' . $category, function ($cookies, $post_id, $field) use ($category) {
            // Only add defaults when empty.
            if ( ! is_array($cookies) ) {
                $cookies = [];
                $key = $field['key'] . "_cookie";
                $base_cookies = apply_filters("ouun.consent.cookies_$category", get_default_cookies($category));

                foreach ($base_cookies as $base_cookie) {
                    foreach ($base_cookie as $base_key => $base_detail) {
                        $base_cookie[$key . '_' . $base_key] = $base_detail;
                        unset($base_cookie[$base_key]);
                    }

                    $cookies[] = [
                        $key => $base_cookie
                    ];
                }
            }

            return $cookies;
        }, 10, 3);
    }

    /**
     * Cookies Shortcode
     * [cookies table (category="")]
     * [cookies description category=""]
     */
    if (!shortcode_exists('cookies')) {
        add_shortcode( 'cookies', function ($attributes) {
            $get = $attributes[0] ?? 'table';

            $attributes = shortcode_atts(
                [
                    'category' => '',
                ],
                $attributes,
                'cookies'
            );

            if($get === 'description' && $attributes['category']) {
                return get_cookies_category_description($attributes['category']);
            } else if ($get === 'table') {
                return get_cookies_table($attributes['category'] ?? '');
            } else {
                return '';
            }
        });
    }

    /**
     * Publishing Date Shortcode
     */
    if (!shortcode_exists('publish_date')) {
        add_shortcode('publish_date', function ($attributes) {
            $attributes = shortcode_atts(
                [
                    'format' => '',
                    'post_id' => null
                ],
                $attributes,
                'publish_date'
            );

            return esc_html( get_the_date( '', $attributes['post_id'] ) );
        });
    }
}

/**
 * Get a Cookies Category Description
 *
 * @param string $category
 * @return string
 */
function get_cookies_category_description(string $category): string
{
    $field = get_field('category_description_' . $category, 'option');
    $default = implode(' ', get_cookie_category_description($category));

    return !empty($field) ? $field : $default;
}

/**
 * Get Cookies in Table Markup
 *
 * @param string $category
 * @return string
 */
function get_cookies_table(string $category = ''): string
{
    $cookies = get_cookies($category);

    $rows = [
        'name' => 'Name',
        'domain' => 'Domain',
        'expires' => 'Expires',
        'description' => 'Description'
    ];

    ob_start();

    if($cookies) {
        ?>
        <table>
            <thead>
            <tr>
                <?php foreach ($rows as $label) { ?>
                    <th><?php echo $label; ?></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($cookies as $cookie) { ?>
                <tr>
                    <?php foreach ($rows as $key => $label) { ?>
                        <td><?php echo $cookie[$key] ?? ''; ?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    }

    $table = ob_get_clean();

    return apply_filters("ouun.consent.default_cookies_table_" . $category, $table);
}

/**
 * Get Cookies from DB
 *
 * @param string $category
 * @return array|bool
 */
function get_cookies(string $category = '')
{
    $cookies = [];
    $cookies_array = [];

    if (!empty($category)) {
        $cookies_array = get_field('field_cookies_category_' . $category);
    } else {
        foreach (array_keys(consent_categories()) as $category) {
            if ($field = get_field('field_cookies_category_' . $category)) {
                $cookies_array = array_merge($cookies_array, $field);
            }
        }
    }

    if ( $cookies_array ) {
        foreach( $cookies_array as $cookie ) {
            $cookies[] = $cookie['cookie'];
        }
    }

    return $cookies;
}

/**
 * Presets WordPress Default Cookies
 *
 * @param $category
 * @return array
 */
function get_default_cookies($category): array
{
    $cookies = [
        'functional' => [
            [
                'name' => preg_replace('#[^_]*$#', '', AUTH_COOKIE),
                'domain' => get_cookie_domain(),
                'expires' => 'End of session',
                'description' => 'First party cookie only for logged in users. On login, we set the cookie to store your authentication details. Its use is limited to the administration area.'
            ],
            [
                'name' => preg_replace('#[^_]*$#', '', LOGGED_IN_COOKIE),
                'domain' => get_cookie_domain(),
                'expires' => 'End of session',
                'description' => 'First party cookie only for logged in users. After login, we set this cookie, which indicates when you’re logged in, and who you are, for most interface use.'
            ],
            [
                'name' => preg_replace('#[^_]*$#', '', TEST_COOKIE),
                'domain' => get_cookie_domain(),
                'expires' => 'End of session',
                'description' => 'First party cookie only for logged in users. Used to test if cookie handling works as expected.'
            ],
            [
                'name' => 'wp-settings-{time}-',
                'domain' => get_cookie_domain(),
                'expires' => '1 year',
                'description' => 'First party cookie only for logged in users. A few settings cookies are used to customize your view of the admin interface, and possibly also the main site interface.'
            ],
            [
                'name' => 'comment_author_',
                'domain' => get_cookie_domain(),
                'expires' => '1 year',
                'description' => 'First party cookie that stores a comment author name if consent is given. This is purely a convenience, so that the visitor won’t need to re-type all their information again when they want to leave another comment.'
            ],
            [
                'name' => 'comment_author_email_',
                'domain' => get_cookie_domain(),
                'expires' => '1 year',
                'description' => 'First party cookie that stores a comment author email if consent is given. This is purely a convenience, so that the visitor won’t need to re-type all their information again when they want to leave another comment.'
            ],
            [
                'name' => 'comment_author_url_',
                'domain' => get_cookie_domain(),
                'expires' => '1 year',
                'description' => 'First party cookie that stores a comment author website URL if consent is given. This is purely a convenience, so that the visitor won’t need to re-type all their information again when they want to leave another comment.'
            ]
        ]
    ];

    // Add Cookie Consent Cookies
    foreach (consent_categories() as $cookies_category => $label) {
        $cookies['preferences'][] = [
            'name' => cookie_prefix() . '_' . $cookies_category,
            'domain' => get_cookie_domain(),
            'expires' => sprintf( __( '%1$s days', 'ouun-consent' ), get_consent_option( 'cookie_expiration' ) ),
            'description' => "Stores your consent for $label Cookies."
        ];
    }

    return apply_filters("ouun.consent.default_cookies_$category", $cookies[$category] ?? []);
}

/**
 * Renders the Cookies Settings Page
 */
function render_ouun_cookies_page() {
    ob_start();

        render_ouun_privacy_page_header();
        ?>
            <div class="privacy-settings-body">
        <?php

    echo ob_get_clean();
}

/**
 * Filter consent for dynamic Category Descriptions via Shortcodes
 */
function overwrite_cookies_categories_description() {
    foreach (consent_categories() as $category => $label) {
        $category_description = get_field('description_' . $category, 'option');
        if (!empty($category_description)) {
            add_filter("ouun.consent.cookie_policy_content_$category", function () use ($category_description) {
                return preg_split('[(<p[^>]*>.*?</p>)]', $category_description, NULL, PREG_SPLIT_DELIM_CAPTURE);
            });
        }

        // Filters the default output e.g. for Policy Page generation
        add_filter("ouun.consent.cookie_default_policy_content_$category", function () use ($category) {
            // Replace the content with a shortcode that outputs ACF field text
            $strings = [];
            $strings[] = '<p>[cookies description category="' . $category . '"]</p>';
            $strings[] = '<p>[cookies table category="' . $category . '"]</p>';

            return $strings;
        });
    }
}

/**
 * Get domain from URL
 *
 * @param string $url
 * @return string
 */
function get_cookie_domain(string $url = ''): string
{
    return parse_url(empty($url) ? get_home_url() : $url, PHP_URL_HOST);
}
