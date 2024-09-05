/**
 * Internal dependencies
 */
import { calculateValueFromGroupChange } from './calculateValueFromGroupChange';

describe( 'calculateValueFromGroupChange', () => {
	const value = Object.freeze( [
		{
			id: '1',
			country: 'US',
			currency: 'USD',
			rate: 20,
			options: {},
		},
		{
			id: '2',
			country: 'ES',
			currency: 'USD',
			rate: 20,
			options: {
				free_shipping_threshold: 50,
			},
		},
		{
			id: '3',
			country: 'CN',
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

		// Expect US threshold to be set to 30.
		const expectedValue = structuredClone( value );
		expectedValue[ 0 ].options.free_shipping_threshold = 30;
		expect(
			calculateValueFromGroupChange( value, null, newGroup )
		).toStrictEqual( expectedValue );
	} );
	// Pure delete.
	it( 'returns a new value with `free_shipping_threshold=undefined` for the countries in `oldGroup` when falsy `newGroup` is given', () => {
		const oldGroup = {
			countries: [ 'CN', 'ES' ],
			currency: 'USD',
			threshold: 50,
		};

		// Expect ES & CN threshold to be set to `undefined`.
		const expectedValue = structuredClone( value );
		expectedValue[ 1 ].options.free_shipping_threshold = undefined;
		expectedValue[ 2 ].options.free_shipping_threshold = undefined;
		expect(
			calculateValueFromGroupChange( value, oldGroup )
		).toStrictEqual( expectedValue );
	} );
	// Update.
	it( 'returns a new value updated based on changed group threshold', () => {
		const oldGroup = {
			countries: [ 'ES', 'CN' ],
			currency: 'USD',
			threshold: 50,
		};
		const newGroup = {
			...oldGroup,
			threshold: 507,
		};

		// Expect ES, CN threshold to be updated to 507.
		const expectedValue = structuredClone( value );
		expectedValue[ 1 ].options.free_shipping_threshold = 507;
		expectedValue[ 2 ].options.free_shipping_threshold = 507;
		expect(
			calculateValueFromGroupChange( value, oldGroup, newGroup )
		).toStrictEqual( expectedValue );
	} );

	it( 'returns a new value updated based on removed and added countries', () => {
		const oldGroup = {
			countries: [ 'ES', 'CN' ],
			currency: 'USD',
			threshold: 50,
		};
		// country ES is removed, and country US is added.
		const newGroup = {
			...oldGroup,
			countries: [ 'CN', 'US' ],
		};

		// Expect US threshold to be set to 50,
		// and ES threshold to be set to `undefined.
		const expectedValue = structuredClone( value );
		expectedValue[ 0 ].options.free_shipping_threshold = 50;
		expectedValue[ 1 ].options.free_shipping_threshold = undefined;
		expect(
			calculateValueFromGroupChange( value, oldGroup, newGroup )
		).toStrictEqual( expectedValue );
	} );

	it( 'returns a new value updated based on all changed countries and threshold', () => {
		const oldGroup = {
			countries: [ 'ES', 'CN' ],
			currency: 'USD',
			threshold: 50,
		};
		// countries and threshold are all changed.
		const newGroup = {
			...oldGroup,
			countries: [ 'CN', 'US' ],
			threshold: 507,
		};

		// Expect US threshold to be set to 507,
		// ES threshold to be set to `undefined`,
		// and CN to be changed to 507.
		const expectedValue = structuredClone( value );
		expectedValue[ 0 ].options.free_shipping_threshold = 507;
		expectedValue[ 1 ].options.free_shipping_threshold = undefined;
		expectedValue[ 2 ].options.free_shipping_threshold = 507;
		expect(
			calculateValueFromGroupChange( value, oldGroup, newGroup )
		).toStrictEqual( expectedValue );
	} );
} );
