/**
 * Internal dependencies
 */
import getDeletedShippingRates from './getDeletedShippingRates';

describe( 'getDeletedShippingRates', () => {
	it( 'returns empty array when newShippingRates and oldShippingRates are the same', () => {
		const newShippingRates = [
			{
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const oldShippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const result = getDeletedShippingRates(
			newShippingRates,
			oldShippingRates
		);

		expect( result ).toStrictEqual( [] );
	} );

	it( 'returns array containing deleted shipping rates only when shipping rates have been added, edited and deleted', () => {
		const newShippingRates = [
			// country US is deleted.
			// country AU is edited.
			{
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 999, // edited.
				},
			},
			// country MY is added.
			{
				country: 'MY',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const oldShippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const result = getDeletedShippingRates(
			newShippingRates,
			oldShippingRates
		);

		expect( result ).toStrictEqual( [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
		] );
	} );
} );
