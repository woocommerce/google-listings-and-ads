/**
 * Internal dependencies
 */
import { changeMinimumOrderGroup } from './changeMinimumOrderGroup';

describe( 'changeMinimumOrderGroup', () => {
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
	// Pure add.
	it( "returns new value with `newGroup.country`'s `shippingRate.options.free_shipping_threshold` updated to `newGroup.threshold`, when falsy `oldGroup` is given", () => {
		const newGroup = {
			countries: [ 'US' ],
			currency: 'USD',
			threshold: 30,
		};

		expect(
			changeMinimumOrderGroup( value, null, newGroup )
		).toStrictEqual( [
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
	// Pure delete.
	it( 'returns a new value with `free_shipping_threshold=undefined` for the countries in `oldGroup` when falsy `newGroup` is given', () => {
		const oldGroup = {
			countries: [ 'CN', 'AU' ],
			currency: 'USD',
			threshold: 50,
		};

		expect( changeMinimumOrderGroup( value, oldGroup ) ).toStrictEqual( [
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
	// Update.
	it( 'returns a new value updated based on changed group threshold', () => {
		const oldGroup = {
			countries: [ 'AU', 'CN' ],
			currency: 'USD',
			threshold: 50,
		};
		const newGroup = {
			...oldGroup,
			threshold: 80,
		};

		expect(
			changeMinimumOrderGroup( value, oldGroup, newGroup )
		).toStrictEqual( [
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
					free_shipping_threshold: 80,
				},
			},
			{
				id: '3',
				country: 'CN',
				method: 'flat_rate',
				currency: 'USD',
				rate: 25,
				options: {
					free_shipping_threshold: 80,
				},
			},
		] );
	} );

	it( 'returns a new value updated based on removed and added countries', () => {
		const oldGroup = {
			countries: [ 'AU', 'CN' ],
			currency: 'USD',
			threshold: 50,
		};
		// country AU is removed, and country US is added.
		const newGroup = {
			...oldGroup,
			countries: [ 'CN', 'US' ],
		};

		expect(
			changeMinimumOrderGroup( value, oldGroup, newGroup )
		).toStrictEqual( [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 50,
				},
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
					free_shipping_threshold: 50,
				},
			},
		] );
	} );

	it( 'returns a new value updated based on all changed countries and threshold', () => {
		const oldGroup = {
			countries: [ 'AU', 'CN' ],
			currency: 'USD',
			threshold: 50,
		};
		// countries and threshold are all changed.
		const newGroup = {
			...oldGroup,
			countries: [ 'CN', 'US' ],
			threshold: 88,
		};

		expect(
			changeMinimumOrderGroup( value, oldGroup, newGroup )
		).toStrictEqual( [
			{
				id: '1',
				country: 'US',
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
				options: {
					free_shipping_threshold: 88,
				},
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
					free_shipping_threshold: 88,
				},
			},
		] );
	} );
} );
