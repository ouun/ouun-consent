/* global wp_set_consent wp_has_consent consent_api_set_cookie consent_api_get_cookie ouunConsent */
/**
 * Handle toggling cookie consent.
 */

// Use the Ouun namespace. Since it's declared as a var, we can't redeclare as a let or const.
var Ouun = window.Ouun || {}; // eslint-disable-line no-var

/**
 * Set up the Ouun.Consent namespace and preload with some variables we'll use in the baner display.
 */
Ouun.Consent = {
	giveConsentButton: document.querySelector( '.give-consent' ),
	revokeConsentButton: document.querySelector( '.revoke-consent' ),
	cookiePreferences: document.querySelector( '.cookie-preferences' )?.classList || false,
	cookiePrefsButton: document.querySelector( '.view-preferences' ),
	applyCookiePrefs: document.querySelector( '.apply-cookie-preferences' ),
	cookieUpdatedMessage: document.querySelector( '.consent-updated-message' ).classList,
	closeUpdatedMessage: document.getElementById( 'consent-close-updated-message' ),
	consentBanner: document.getElementById( 'cookie-consent-banner' ),
};

/**
 * Check if a user has given consent for a specific category.
 *
 * Wrapper function for wp_has_consent.
 *
 * @param {string} category The category to check consent against.
 * @returns {boolean}           Whether the user has given consent for the given category.
 */
Ouun.Consent.has = function ( category ) {
	return wp_has_consent( category );
};

/**
 * Set a new consent category value.
 *
 * Wrapper function for wp_set_consent.
 *
 * @param {string} category The consent category to update.
 * @param {string} value The value to update the consent category to.
 */
Ouun.Consent.set = function ( category, value ) {
	wp_set_consent( category, value );
};

/**
 * Set cookie by consent type.
 *
 * Wrapper function for consent_api_set_cookie.
 *
 * @param {string} name The cookie name to set.
 * @param {string} value The cookie value to set.
 */
Ouun.Consent.setCookie = function ( name, value ) { // eslint-disable-line no-unused-vars
	consent_api_set_cookie( name, value );
};

/**
 * Retrieve a cookie by name.
 *
 * Wrapper function for consent_api_get_cookie.
 *
 * @param {string} name The name of the cookie to get data from.
 * @returns {boolean}       Cookie data for the given cookie, if it exists.
 */
Ouun.Consent.getCookie = function ( name ) { // eslint-disable-line no-unused-vars
	return consent_api_get_cookie( name );
};

/**
 * Check if a consent cookie has already been saved on the client machine.
 *
 * @returns {boolean} Return true if consent has been given previously.
 */
Ouun.Consent.cookieSaved = function () {
	let consentExists = false;
	ouunConsent.categories
		// Skip the functional cookies preference.
		.filter( category => category !== 'functional' )
		// Loop through the rest of the categories.
		.forEach( category => {
			if ( Ouun.Consent.has( category ) ) {
				consentExists = true;
			}

			return;
		} );

	return consentExists;
};

/**
 * Return an array of all the categories that a user has consented to.
 *
 * @returns {Array} An array of allowed cookie categories.
 */
Ouun.Consent.getCategories = function () { // eslint-disable-line no-unused-vars
	// Start off with the allowlisted categories.
	let hasConsent = ouunConsent.alwaysAllowCategories;

	ouunConsent.categories.forEach( category => {
		if ( Ouun.Consent.has( category ) ) {
			hasConsent.push( category );
		}
	} );

	return hasConsent;
};

/**
 * Update consent for individual categories.
 */
Ouun.Consent.updateCategories = function () {
	const categories = document.getElementsByClassName( 'category-input' );
	let selected   = [],
		unselected = [];

	// If we're selecting categories from inputs, add the selected categories to an array and the unselected categories to a different array.
	if ( Ouun.Consent.cookiePreferences && Ouun.Consent.cookiePreferences.contains( 'show' ) ) {
		for ( const category of categories ) {
			if ( category.checked ) {
				selected.push( category.value );
			} else {
				unselected.push( category.value );
			}
		}
	}

	// If we're consenting to all cookies, add all categories to the selected array.
	if ( this.className === 'give-consent' ) {
		// Accept all categories.
		for ( const category of ouunConsent.categories ) {
			selected.push( category );
		}

		unselected = [];
	} else if ( this.className === 'revoke-consent' ) {
		// If we're only consenting to functional cookies, only add that category to selected and all others to unselected.
		for ( const category of ouunConsent.categories ) {
			if ( ! ouunConsent.alwaysAllowCategories.includes( category ) ) {
				unselected.push( category );
			}
		}
	}

	// Add the categories that we're always allowing.
	selected.push( ...ouunConsent.alwaysAllowCategories );

	Array.from( new Set( selected ) );
	Array.from( new Set( unselected ) );

	// Set the allowed categories.
	for ( const selectedCategory of selected ) {
		Ouun.Consent.set( selectedCategory, 'allow' );
	}

	// Set the disallowed categories.
	for ( const unselectedCategory of unselected ) {
		Ouun.Consent.set( unselectedCategory, 'deny' );
	}

	// Toggle the cookie preferences if we've passed specific categories.
	if ( Ouun.Consent.cookiePreferences && Ouun.Consent.cookiePreferences.contains( 'show' ) ) {
		Ouun.Consent.cookiePreferences.remove( 'show' );

		// Show the buttons if they are hidden.
		Ouun.Consent.giveConsentButton.classList.remove( 'hide' );
		Ouun.Consent.revokeConsentButton.classList.remove( 'hide' );
	}

	document.querySelector( '.consent-banner' ).classList.add( 'hide' );

	Ouun.Consent.preferencesUpdatedMessage();
};

/**
 * Show or hide the cookie preferences.
 */
Ouun.Consent.toggleCookiePrefs = function () {
	const allowAllClasses      = Ouun.Consent.giveConsentButton.classList,
		allowFunctionalClasses = Ouun.Consent.revokeConsentButton.classList;

	Ouun.Consent.cookiePreferences.toggle( 'show' );

	// Toggle the other buttons when we show the cookie prefs.
	allowAllClasses.toggle( 'hide' );
	allowFunctionalClasses.toggle( 'hide' );
};

/**
 * Check if consent has been given already. If not, toggle display of the banner.
 */
Ouun.Consent.maybeDisplayBanner = function () {
	if (
		// A consent cookie has not been saved...
		! Ouun.Consent.cookieSaved() &&
		// We're not hiding the banner in the settings...
		ouunConsent.shouldDisplayBanner &&
		// & the banner markup exists.
		Ouun.Consent.consentBanner
	) {
		// Display the consent banner.
		Ouun.Consent.consentBanner.style.display = 'block';
	}
};

/**
 * Show the preferences updated message.
 */
Ouun.Consent.preferencesUpdatedMessage = function () {
	const consentUpdated = document.querySelector( '.consent-updated-message' ).classList;
	consentUpdated.toggle( 'show' );
};

// Display the banner. Or not.
Ouun.Consent.maybeDisplayBanner();

// Toggle consent when grant/revoke consent button is clicked.
Ouun.Consent.giveConsentButton.addEventListener( 'click', Ouun.Consent.updateCategories );
Ouun.Consent.revokeConsentButton.addEventListener( 'click', Ouun.Consent.updateCategories );

// Make sure the preverences button exists before triggering an on-click action.
if ( Ouun.Consent.cookiePrefsButton ) {
	Ouun.Consent.cookiePrefsButton.addEventListener( 'click', Ouun.Consent.toggleCookiePrefs );
	Ouun.Consent.applyCookiePrefs.addEventListener( 'click', Ouun.Consent.updateCategories );
}

// Close the banner if the close button is clicked.
Ouun.Consent.closeUpdatedMessage.addEventListener( 'click', () => Ouun.Consent.cookieUpdatedMessage.toggle( 'show' ) );
