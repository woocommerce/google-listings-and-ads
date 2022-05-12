/**
 * Internal dependencies
 */
import { deleteGroup } from './getMinimumOrderDeleteHandler';

describe( 'deleteGroup( value, oldGroup )', () => {
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

	it( 'returns a new value, with free_shipping_threshold removed for the countries in `oldGroup`', () => {
		const oldGroup = {
			countries: [ 'CN', 'AU' ],
			currency: 'USD',
			threshold: 50,
		};

		expect( deleteGroup( value, oldGroup ) ).toStrictEqual( [
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
