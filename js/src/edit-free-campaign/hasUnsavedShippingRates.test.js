/**
 * Internal dependencies
 */
import hasUnsavedShippingRates from './hasUnsavedShippingRates';

describe( 'hasUnsavedShippingRates', () => {
	it( 'returns false when both shipping rates are the same', () => {
		const newShippingRates = [
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

		const result = hasUnsavedShippingRates(
			newShippingRates,
			oldShippingRates
		);

		expect( result ).toBe( false );
	} );

	it( 'returns true when there are deleted shipping rates', () => {
		const newShippingRates = [
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

		const result = hasUnsavedShippingRates(
			newShippingRates,
			oldShippingRates
		);

		expect( result ).toBe( true );
	} );

	it( 'returns true when there are new shipping rates', () => {
		const newShippingRates = [
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
			{
				// new shipping rate
				country: 'MY',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
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

		const result = hasUnsavedShippingRates(
			newShippingRates,
			oldShippingRates
		);

		expect( result ).toBe( true );
	} );

	it( `returns true when shipping rates' free_shipping_threshold are edited`, () => {
		const newShippingRates = [
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
					free_shipping_threshold: 1500, // edited.
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

		const result = hasUnsavedShippingRates(
			newShippingRates,
			oldShippingRates
		);

		expect( result ).toBe( true );
	} );

	it( `returns true when shipping rates' rate are edited`, () => {
		const newShippingRates = [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 2000, // edited
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

		const result = hasUnsavedShippingRates(
			newShippingRates,
			oldShippingRates
		);

		expect( result ).toBe( true );
	} );
} );
