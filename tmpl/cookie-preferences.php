<?php
/**
 * Cookie Preferences
 *
 * @package Ouun-Consent
 */

use Ouun\Consent;

$categories = Consent\consent_categories();
?>

<div class="cookie-preferences">
    <?php
    foreach ($categories as $category => $label) {
        // Validate the consent category.
        if (! Consent\validate_consent_item($category, 'categories')) {
            continue;
        }

        // Skip anonymous statistics category, don't need to ask permission explicitly.
        if ('statistics-anonymous' === $category) {
            continue;
        }
        ?>

        <label for="cookie-preference-<?php echo esc_attr($category); ?>">
            <input type="checkbox" name="cookie-preferences[<?php echo esc_attr($category); ?>]" class="category-input" value="<?php echo esc_attr($category); ?>"
                <?php if ('functional' === $category) : ?>
                    checked="checked" disabled="disabled"
                <?php endif; ?>
                data-consentcategory="<?php echo esc_attr($category); ?>"
            />
            <?php echo $label; ?>
        </label>
    <?php } ?>

    <button class="button apply-cookie-preferences">
        <?php echo esc_html(apply_filters('ouun.consent.apply_cookie_preferences_button_text', __('Apply Changes', 'ouun-consent'))); ?>
    </button>

    <button class="button close-preferences">
        <?php echo esc_html(apply_filters('ouun.consent.cookie_preferences_close_button_text', __('Cancel', 'ouun-consent'))); ?>
    </button>
</div>
