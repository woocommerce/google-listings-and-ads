/**
 * External dependencies
 */
import { getQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import {
	addReferrerParams,
	getAccountAwareUrl,
	getAccountsSettingsUrl,
} from '~/utils/urls';

describe( 'getAccountsSettingsUrl', () => {
	it( 'returns the accounts settings URL', () => {
		expect( getQueryArgs( getAccountsSettingsUrl() ) ).toEqual( {
			page: 'wc-admin',
			path: '/google/settings',
			section: 'accounts',
		} );
	} );

	it( 'adds extra query args, without letting them override the section', () => {
		const url = getAccountsSettingsUrl( {
			'focus-account-card': 'tag-manager',
			section: 'general',
		} );

		expect( getQueryArgs( url ) ).toEqual( {
			page: 'wc-admin',
			path: '/google/settings',
			section: 'accounts',
			'focus-account-card': 'tag-manager',
		} );
	} );
} );

describe( 'getAccountAwareUrl', () => {
	it( 'wraps the destination URL in an accountchooser redirect for the given email', () => {
		expect(
			getAccountAwareUrl(
				'https://example.com/report',
				'merchant@example.com'
			)
		).toBe(
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fexample.com%2Freport&Email=merchant%40example.com'
		);
	} );

	it( 'returns the destination URL unwrapped when email is missing', () => {
		expect(
			getAccountAwareUrl( 'https://example.com/report', undefined )
		).toBe( 'https://example.com/report' );
	} );
} );

describe( 'addReferrerParams', () => {
	it( 'appends referrer_type and referrer_id query params to the given href', () => {
		expect(
			addReferrerParams( '/onboarding', 'in_product_placements', 'foo' )
		).toBe(
			'/onboarding?referrer_type=in_product_placements&referrer_id=foo'
		);
	} );

	it( 'preserves existing query params on the href', () => {
		expect(
			addReferrerParams(
				'/onboarding?foo=bar',
				'in_product_placements',
				'baz'
			)
		).toBe(
			'/onboarding?foo=bar&referrer_type=in_product_placements&referrer_id=baz'
		);
	} );
} );
