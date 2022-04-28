/**
 * Internal dependencies
 */
import {
	handleAdd,
	handleChange,
	handleDelete,
} from './getMinimumOrderHandlers';

describe( 'getMinimumOrderHandlers', () => {
	let mockOnChange;
	let value;

	beforeEach( () => {
		value = [
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
		];

		mockOnChange = jest.fn().mockName( 'onChange' );
	} );

	describe( 'handleAdd', () => {
		it( 'calls `onChange` with the newly added group', () => {
			const newGroup = {
				countries: [ 'US' ],
				currency: 'USD',
				threshold: 30,
			};

			handleAdd( value, mockOnChange, newGroup );

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

	describe( 'handleChange', () => {
		it( 'calls `onChange` with the value updated based on changed group threshold', () => {
			const oldGroup = {
				countries: [ 'AU', 'CN' ],
				currency: 'USD',
				threshold: 50,
			};
			const newGroup = {
				...oldGroup,
				threshold: 80,
			};

			handleChange( value, mockOnChange, oldGroup, newGroup );

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

		it( 'calls `onChange` with the value updated based on removed and added countries', () => {
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

			handleChange( value, mockOnChange, oldGroup, newGroup );

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

		it( 'calls `onChange` with the value updated based on all changed countries and threshold', () => {
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

			handleChange( value, mockOnChange, oldGroup, newGroup );

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

	describe( 'handleDelete', () => {
		it( 'calls `onChange` with the value without the deleted group', () => {
			const oldGroup = {
				countries: [ 'CN', 'AU' ],
				currency: 'USD',
				threshold: 50,
			};

			handleDelete( value, mockOnChange, oldGroup );

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
} );
