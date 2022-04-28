/**
 * Internal dependencies
 */
import getMinimumOrderDeleteHandler from './getMinimumOrderDeleteHandler';

describe( 'getMinimumOrderDeleteHandler( value, onChange )( oldGroup )()', () => {
	const value = Object.freeze( [
		{
			id: '1',
			country: 'US',
			method: 'flat_rate',
			currency: 'USD',
			rate: 20,
			options: {},
		},
		{
			id: '2',
			country: 'AU',
			method: 'flat_rate',
			currency: 'USD',
			rate: 20,
			options: {
				free_shipping_threshold: 50,
			},
		},
		{
			id: '3',
			country: 'CN',
			method: 'flat_rate',
			currency: 'USD',
			rate: 25,
			options: {
				free_shipping_threshold: 50,
			},
		},
	] );
	const mockOnChange = jest.fn();

	afterEach( () => {
		mockOnChange.mockClear();
	} );

	it( 'Calls the `onChange` callback with new value, with free_shipping_threshold removed for the countries in `oldGroup`', () => {
		const oldGroup = {
			countries: [ 'CN', 'AU' ],
			currency: 'USD',
			threshold: 50,
		};

		getMinimumOrderDeleteHandler( value, mockOnChange )( oldGroup )();

		expect( mockOnChange.mock.calls.length ).toBe( 1 );
		expect( mockOnChange.mock.calls[ 0 ][ 0 ] ).toStrictEqual( [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: undefined,
				},
			},
			{
				id: '3',
				country: 'CN',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: undefined,
				},
			},
		] );
	} );
} );
