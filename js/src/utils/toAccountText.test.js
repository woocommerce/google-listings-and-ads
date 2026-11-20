/**
 * Internal dependencies
 */
import toAccountText from './toAccountText';

describe( 'toAccountText', () => {
	it( 'formats 10-digit Google Ads account IDs with hyphens', () => {
		expect( toAccountText( 5647863919 ) ).toBe( '564-786-3919' );
	} );

	it( 'returns non-10-digit IDs unchanged', () => {
		expect( toAccountText( 12345 ) ).toBe( '12345' );
	} );
} );
