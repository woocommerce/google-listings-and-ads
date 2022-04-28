/**
 * Internal dependencies
 */
import getMinimumOrderChangeHandler from './getMinimumOrderChangeHandler';

describe( 'getMinimumOrderChangeHandler( value, onChange )( oldGroup )( newGroup )', () => {
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

	it( 'Calls the `onChange` callback with new value updated based on changed group threshold', () => {
		const oldGroup = {
			countries: [ 'AU', 'CN' ],
			currency: 'USD',
			threshold: 50,
		};
		const newGroup = {
			...oldGroup,
			threshold: 80,
		};

		getMinimumOrderChangeHandler( value, mockOnChange )( oldGroup )(
			newGroup
		);

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

	it( 'Calls the `onChange` callback with new value updated based on removed and added countries', () => {
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

		getMinimumOrderChangeHandler( value, mockOnChange )( oldGroup )(
			newGroup
		);

		expect( mockOnChange.mock.calls.length ).toBe( 1 );
		expect( mockOnChange.mock.calls[ 0 ][ 0 ] ).toStrictEqual( [
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

	it( 'Calls the `onChange` callback with new value updated based on all changed countries and threshold', () => {
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

		getMinimumOrderChangeHandler( value, mockOnChange )( oldGroup )(
			newGroup
		);

		expect( mockOnChange.mock.calls.length ).toBe( 1 );
		expect( mockOnChange.mock.calls[ 0 ][ 0 ] ).toStrictEqual( [
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
