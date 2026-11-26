/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import useStoreCountry from './useStoreCountry';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	OPTIONS_STORE_NAME: 'wc/admin/options',
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn(),
} ) );

/**
 * Mocks the `getOption( 'woocommerce_default_country' )` selector state.
 *
 * @param {*} value The value the selector returns.
 * @param {boolean} finished Whether the resolution has finished.
 */
function mockDefaultCountryOption( value, finished ) {
	useSelect.mockImplementation( ( callback ) =>
		callback( () => ( {
			getOption: () => value,
			hasFinishedResolution: () => finished,
		} ) )
	);
}

describe( 'useStoreCountry', () => {
	beforeEach( () => {
		getSetting.mockReturnValue( { EE: 'Estonia', US: 'United States' } );
	} );

	it( 'returns nulls while the option is still resolving', () => {
		mockDefaultCountryOption( undefined, false );

		const { result } = renderHook( () => useStoreCountry() );

		expect( result.current ).toEqual( { code: null, name: null } );
	} );

	it( 'returns the code and name for a country with a state suffix', () => {
		mockDefaultCountryOption( 'US:CA', true );

		const { result } = renderHook( () => useStoreCountry() );

		expect( result.current ).toEqual( {
			code: 'US',
			name: 'United States',
		} );
	} );

	it( 'returns the code and name for a bare country code', () => {
		mockDefaultCountryOption( 'EE', true );

		const { result } = renderHook( () => useStoreCountry() );

		expect( result.current ).toEqual( { code: 'EE', name: 'Estonia' } );
	} );

	it( 'returns nulls instead of throwing when the option request failed', () => {
		mockDefaultCountryOption( undefined, true );

		const { result } = renderHook( () => useStoreCountry() );

		expect( result.current ).toEqual( { code: null, name: null } );
	} );
} );
