/**
 * Internal dependencies
 */
import getDifferentShippingRates from './getDifferentShippingRates';

describe( 'getDifferentShippingRates', () => {
	it( 'returns empty array when shippingRates1 and shippingRates2 are the same', () => {
		const shipingRates1 = [
			{
				country: 'US',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				country: 'AU',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const shipingRates2 = [
			{
				id: '1',
				country: 'US',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const result = getDifferentShippingRates(
			shipingRates1,
			shipingRates2
		);

		expect( result ).toStrictEqual( [] );
	} );

	it( 'returns array containing newly added shipping rates from shippingRates1', () => {
		const shipingRates1 = [
			{
				country: 'US',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				country: 'AU',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
			{
				// new shipping rate
				country: 'MY',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 100,
				},
			},
			{
				// new shipping rate
				country: 'SG',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 200,
				},
			},
		];

		const shipingRates2 = [
			{
				id: '1',
				country: 'US',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const result = getDifferentShippingRates(
			shipingRates1,
			shipingRates2
		);

		expect( result ).toStrictEqual( [
			{
				country: 'MY',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 100,
				},
			},
			{
				country: 'SG',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 200,
				},
			},
		] );
	} );

	it( 'returns array containing edited shipping rates from shippingRates1', () => {
		const shipingRates1 = [
			{
				country: 'US',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				country: 'AU',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 999, // edited
				},
			},
			{
				country: 'MY',
				currency: 'USD',
				rate: 888, // edited
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const shipingRates2 = [
			{
				id: '1',
				country: 'US',
				currency: 'USD',
				rate: 4.99,
				options: {},
			},
			{
				id: '2',
				country: 'AU',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 100,
				},
			},
			{
				id: '3',
				country: 'MY',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 100,
				},
			},
		];

		const result = getDifferentShippingRates(
			shipingRates1,
			shipingRates2
		);

		expect( result ).toStrictEqual( [
			{
				country: 'AU',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 999,
				},
			},
			{
				country: 'MY',
				currency: 'USD',
				rate: 888,
				options: {
					free_shipping_threshold: 100,
				},
			},
		] );
	} );
} );
