const SCOPE = {
	// Manage product listings and accounts for Google Shopping
	CONTENT: 'https://www.googleapis.com/auth/content',
	// Manage new site verifications with Google
	SITE_VERIFICATION_VERIFY_ONLY:
		'https://www.googleapis.com/auth/siteverification.verify_only',
	// Manage AdWords campaigns
	AD_WORDS: 'https://www.googleapis.com/auth/adwords',
};

/**
 * @typedef {Object} ScopeState
 * @property {boolean} gmcRequired Whether has the required scopes of Google Merchant Center.
 * @property {boolean} adsRequired Whether has the required scopes of Google Ads.
 * @property {boolean} glaRequired Whether has the required scopes of GLA plugin based on the current GLA setup.
 *     If the user has completed Google Ads setup, all scopes are required,
 *     otherwise only Google Merchant Center scopes are required.
 */

/**
 * Convert the authorization scopes to a state that whether the minimum required scopes for each function are met.
 *
 * @param {boolean} adsSetupComplete Whether Google Ads setup has been completed.
 *     It should be the `glaData.adsSetupComplete` value imported from {@link .~/constants.glaData}.
 * @param {Array<string>} [scopes=[]] User authorized scopes returned from Google OAuth API.
 * @return {ScopeState} A state that whether the minimum required scopes for each function are met.
 */
export default function toScopeState( adsSetupComplete, scopes = [] ) {
	const state = {
		adsRequired: scopes.includes( SCOPE.AD_WORDS ),
	};

	state.gmcRequired =
		scopes.includes( SCOPE.CONTENT ) &&
		scopes.includes( SCOPE.SITE_VERIFICATION_VERIFY_ONLY );

	state.glaRequired = adsSetupComplete
		? state.gmcRequired && state.adsRequired
		: state.gmcRequired;

	return state;
}
