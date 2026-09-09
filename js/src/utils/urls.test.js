/**
 * Internal dependencies
 */
import {
	addReferrerParams,
	getAccountAwareUrl,
	getSearchConsolePropertyUrl,
	getSearchConsolePerformanceReportUrl,
} from '~/utils/urls';

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

describe( 'getSearchConsolePropertyUrl', () => {
	it( "builds the Search Console URL for the property's siteUrl", () => {
		expect( getSearchConsolePropertyUrl( 'https://example.com/' ) ).toBe(
			'https://search.google.com/search-console?resource_id=https%3A%2F%2Fexample.com%2F'
		);
	} );

	it( 'returns null when siteUrl is not set', () => {
		expect( getSearchConsolePropertyUrl( undefined ) ).toBeNull();
	} );
} );

describe( 'getSearchConsolePerformanceReportUrl', () => {
	it( "builds the Search Console Performance report URL for the property's siteUrl", () => {
		expect(
			getSearchConsolePerformanceReportUrl( 'https://example.com/' )
		).toBe(
			'https://search.google.com/search-console/performance/search-analytics?resource_id=https%3A%2F%2Fexample.com%2F'
		);
	} );

	it( 'returns null when siteUrl is not set', () => {
		expect( getSearchConsolePerformanceReportUrl( undefined ) ).toBeNull();
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
