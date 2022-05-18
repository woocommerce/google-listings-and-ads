/**
 * Internal dependencies
 */
import getMinimumOrderAddHandler from './getMinimumOrderAddHandler';

describe( 'getMinimumOrderAddHandler( value, onChange )( newGroup )', () => {
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

	it( 'Calls the `onChange` callback with new value (`shippingRate.options.free_shipping_threshold` set to `newGroup.threshold`)', () => {
		const newGroup = {
			countries: [ 'US' ],
			currency: 'USD',
			threshold: 30,
		};

		getMinimumOrderAddHandler( value, mockOnChange )( newGroup );

		expect( mockOnChange.mock.calls.length ).toBe( 1 );
		expect( mockOnChange.mock.calls[ 0 ][ 0 ] ).toStrictEqual( [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 30,
				},
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
	} );
} );
