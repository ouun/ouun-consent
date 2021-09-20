<?php
/**
 * Consent updated message
 *
 * @package Ouun-Consent
 */

?>
<div class="consent-updated-message">
	<p class="message">
		<span class="preferences-updated-message">
			<?php echo wp_kses_post( apply_filters( 'ouun.consent.preferences_updated_message', __( 'Cookie preferences updated.', 'ouun-consent' ) ) ); ?>
		</span>
		<button class="close-message" id="consent-close-updated-message"><?php esc_html_e( 'Close', 'ouun-consent' ); ?></button>
	</p>
</div>
