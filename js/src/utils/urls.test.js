/**
 * Internal dependencies
 */
import { getReferrerUrl } from '~/utils/urls';

describe( 'getReferrerUrl', () => {
	it( 'appends referrer_type and referrer_id query params to the given href', () => {
		expect(
			getReferrerUrl( '/onboarding', 'in_product_placements', 'foo' )
		).toBe(
			'/onboarding?referrer_type=in_product_placements&referrer_id=foo'
		);
	} );

	it( 'preserves existing query params on the href', () => {
		expect(
			getReferrerUrl(
				'/onboarding?foo=bar',
				'in_product_placements',
				'baz'
			)
		).toBe(
			'/onboarding?foo=bar&referrer_type=in_product_placements&referrer_id=baz'
		);
	} );
} );
