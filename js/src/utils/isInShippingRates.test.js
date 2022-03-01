/**
 * Internal dependencies
 */
import isInShippingRates from './isInShippingRates';

describe( 'isInShippingRates', () => {
	it( 'should return true when shippingRate exists in shippingRates, matching by id', () => {
		const shippingRate = {
			id: '2',
			country: 'AU',
			method: 'flat_rate',
			currency: 'USD',
			rate: 25,
			options: [],
		};
		const shippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 4.99,
				options: [],
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: [],
			},
			{
				id: '3',
				country: 'MY',
				method: 'flat_rate',
				currency: 'USD',
				rate: 40,
				options: [],
			},
		];

		const result = isInShippingRates( shippingRate, shippingRates );

		expect( result ).toBe( true );
	} );

	it( 'should return true when shippingRate exists in shippingRates, matching by country and method without id', () => {
		const shippingRateWithoutId = {
			country: 'AU',
			method: 'flat_rate',
			currency: 'USD',
			rate: 25,
			options: [],
		};
		const shippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 4.99,
				options: [],
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: [],
			},
			{
				id: '3',
				country: 'MY',
				method: 'flat_rate',
				currency: 'USD',
				rate: 40,
				options: [],
			},
		];

		const result = isInShippingRates(
			shippingRateWithoutId,
			shippingRates
		);

		expect( result ).toBe( true );
	} );

	it( 'should return false when shippingRate does not exists in shippingRates, matching by id', () => {
		const shippingRate = {
			id: '100',
			country: 'BR',
			method: 'flat_rate',
			currency: 'USD',
			rate: 99,
			options: [],
		};
		const shippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 4.99,
				options: [],
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: [],
			},
			{
				id: '3',
				country: 'MY',
				method: 'flat_rate',
				currency: 'USD',
				rate: 40,
				options: [],
			},
		];

		const result = isInShippingRates( shippingRate, shippingRates );

		expect( result ).toBe( false );
	} );

	it( 'should return false when shippingRate does not exist in shippingRates, matching by country and method without id', () => {
		const shippingRateWithoutId = {
			country: 'BR',
			method: 'flat_rate',
			currency: 'USD',
			rate: 99,
			options: [],
		};
		const shippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 4.99,
				options: [],
			},
			{
				id: '2',
				country: 'AU',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: [],
			},
			{
				id: '3',
				country: 'MY',
				method: 'flat_rate',
				currency: 'USD',
				rate: 40,
				options: [],
			},
		];

		const result = isInShippingRates(
			shippingRateWithoutId,
			shippingRates
		);

		expect( result ).toBe( false );
	} );
} );
