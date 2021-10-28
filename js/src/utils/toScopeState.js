const SCOPE = {
	// Manage product listings and accounts for Google Shopping
	CONTENT: 'https://www.googleapis.com/auth/content',
	// Manage new site verifications with Google
	SITE_VERIFICATION_VERIFY_ONLY:
		'https://www.googleapis.com/auth/siteverification.verify_only',
	// Manage AdWords campaigns
	AD_WORDS: 'https://www.googleapis.com/auth/adwords',
};

export default function toScopeState( scopes = [] ) {
	const state = {
		adsRequired: scopes.includes( SCOPE.AD_WORDS ),
	};

	state.gmcRequired =
		scopes.includes( SCOPE.CONTENT ) &&
		scopes.includes( SCOPE.SITE_VERIFICATION_VERIFY_ONLY );

	state.allRequired = state.gmcRequired && state.adsRequired;
	return state;
}
