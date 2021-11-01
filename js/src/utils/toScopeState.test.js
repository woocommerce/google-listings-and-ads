/**
 * Internal dependencies
 */
import toScopeState from './toScopeState';

const SCOPE = {
	// Manage product listings and accounts for Google Shopping
	CONTENT: 'https://www.googleapis.com/auth/content',
	// Manage new site verifications with Google
	SITE_VERIFICATION_VERIFY_ONLY:
		'https://www.googleapis.com/auth/siteverification.verify_only',
	// Manage AdWords campaigns
	AD_WORDS: 'https://www.googleapis.com/auth/adwords',
};

describe( 'toScopeState', () => {
	let contentScopes;
	let siteVerificationScopes;
	let gmcScopes;
	let adsScopes;
	let allScopes;

	beforeEach( () => {
		contentScopes = [ SCOPE.CONTENT ];
		siteVerificationScopes = [ SCOPE.SITE_VERIFICATION_VERIFY_ONLY ];
		gmcScopes = [ SCOPE.CONTENT, SCOPE.SITE_VERIFICATION_VERIFY_ONLY ];
		adsScopes = [ SCOPE.AD_WORDS ];
		allScopes = [
			SCOPE.CONTENT,
			SCOPE.SITE_VERIFICATION_VERIFY_ONLY,
			SCOPE.AD_WORDS,
		];
	} );

	it( 'should return an object structure containing three properties', () => {
		const scopeState = toScopeState( false, [] );

		expect( scopeState ).toHaveProperty( 'gmcRequired' );
		expect( scopeState ).toHaveProperty( 'adsRequired' );
		expect( scopeState ).toHaveProperty( 'glaRequired' );
	} );

	it( 'when the `scopes` parameter is not given, should get `false` for all results', () => {
		expect( toScopeState( false ) ).toMatchObject( {
			gmcRequired: false,
			adsRequired: false,
			glaRequired: false,
		} );
		expect( toScopeState( true ) ).toMatchObject( {
			gmcRequired: false,
			adsRequired: false,
			glaRequired: false,
		} );
	} );

	describe.each( [ [ false ], [ true ] ] )(
		'For `gmcRequired`, if the parameter `adsSetupComplete` = %p',
		( adsSetupComplete ) => {
			it( 'and the `scopes` contains GMC required scopes, should be `true`', () => {
				expect(
					toScopeState( adsSetupComplete, gmcScopes ).gmcRequired
				).toBe( true );
			} );

			it( "and the `scopes` doesn't contains GMC required scopes, should be `false`", () => {
				expect( toScopeState( adsSetupComplete, [] ).gmcRequired ).toBe(
					false
				);
				expect(
					toScopeState( adsSetupComplete, contentScopes ).gmcRequired
				).toBe( false );
				expect(
					toScopeState( adsSetupComplete, siteVerificationScopes )
						.gmcRequired
				).toBe( false );
				expect(
					toScopeState( adsSetupComplete, adsScopes ).gmcRequired
				).toBe( false );
			} );
		}
	);

	describe.each( [ [ false ], [ true ] ] )(
		'For `adsRequired`, if the parameter `adsSetupComplete` = %p',
		( adsSetupComplete ) => {
			it( 'and the `scopes` contains Google Ads required scopes, should be `true`', () => {
				expect(
					toScopeState( adsSetupComplete, adsScopes ).adsRequired
				).toBe( true );
			} );

			it( "and the `scopes` doesn't contains Google Ads required scopes, should be `false`", () => {
				expect( toScopeState( adsSetupComplete, [] ).adsRequired ).toBe(
					false
				);
				expect(
					toScopeState( adsSetupComplete, contentScopes ).adsRequired
				).toBe( false );
				expect(
					toScopeState( adsSetupComplete, siteVerificationScopes )
						.adsRequired
				).toBe( false );
				expect(
					toScopeState( adsSetupComplete, gmcScopes ).adsRequired
				).toBe( false );
			} );
		}
	);

	describe( 'For `glaRequired`', () => {
		describe( 'if the parameter `adsSetupComplete` = false`', () => {
			it( 'and the `scopes` contains GMC required scopes, should be `true`', () => {
				expect( toScopeState( false, gmcScopes ).glaRequired ).toBe(
					true
				);
			} );

			it( "and the `scopes` doesn't contains GMC required scopes, should be `false`", () => {
				expect( toScopeState( false, [] ).glaRequired ).toBe( false );
				expect( toScopeState( false, contentScopes ).glaRequired ).toBe(
					false
				);
				expect(
					toScopeState( false, siteVerificationScopes ).glaRequired
				).toBe( false );
				expect( toScopeState( false, adsScopes ).glaRequired ).toBe(
					false
				);
			} );
		} );

		describe( 'if the parameter `adsSetupComplete` = true`', () => {
			it( 'and the `scopes` contains all required scopes, should be `true`', () => {
				expect( toScopeState( true, allScopes ).glaRequired ).toBe(
					true
				);
			} );

			it( "and the `scopes` doesn't contains all required scopes, should be `false`", () => {
				expect( toScopeState( true, [] ).glaRequired ).toBe( false );
				expect( toScopeState( true, contentScopes ).glaRequired ).toBe(
					false
				);
				expect(
					toScopeState( true, siteVerificationScopes ).glaRequired
				).toBe( false );
				expect( toScopeState( true, adsScopes ).glaRequired ).toBe(
					false
				);
				expect( toScopeState( true, gmcScopes ).glaRequired ).toBe(
					false
				);
			} );
		} );
	} );
} );
