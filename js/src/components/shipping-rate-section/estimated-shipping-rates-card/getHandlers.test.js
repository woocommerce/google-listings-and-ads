/**
 * Internal dependencies
 */
import getHandlers from './getHandlers';

describe( 'getHandlers', () => {
	let mockOnChange;
	let handlers;

	beforeEach( () => {
		const value = [
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

		mockOnChange = jest.fn();
		handlers = getHandlers( {
			value,
			onChange: mockOnChange,
		} );
	} );

	describe( 'handleAddSubmit', () => {
		it( 'returns value with the newly added group', () => {
			const newGroup = {
				countries: [ 'MY', 'SG' ],
				method: 'flat_rate',
				currency: 'USD',
				rate: 30,
			};

			const { handleAddSubmit } = handlers;
			handleAddSubmit( newGroup );

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
				{
					country: 'MY',
					method: 'flat_rate',
					currency: 'USD',
					rate: 30,
					options: {},
				},
				{
					country: 'SG',
					method: 'flat_rate',
					currency: 'USD',
					rate: 30,
					options: {},
				},
			] );
		} );
	} );

	describe( 'getChangeHandler', () => {
		it( 'returns value updated based on changed group rate', () => {
			const oldGroup = {
				countries: [ 'US', 'AU' ],
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
			};
			const newGroup = {
				...oldGroup,
				rate: 25,
			};

			const { getChangeHandler } = handlers;
			getChangeHandler( oldGroup )( newGroup );

			expect( mockOnChange.mock.calls.length ).toBe( 1 );
			expect( mockOnChange.mock.calls[ 0 ][ 0 ] ).toStrictEqual( [
				{
					id: '1',
					country: 'US',
					method: 'flat_rate',
					currency: 'USD',
					rate: 25,
					options: {},
				},
				{
					id: '2',
					country: 'AU',
					method: 'flat_rate',
					currency: 'USD',
					rate: 25,
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

		it( 'returns value with no free_shipping_threshold if the group rate is updated to 0', () => {
			const oldGroup = {
				countries: [ 'US', 'AU' ],
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
			};
			const newGroup = {
				...oldGroup,
				rate: 0,
			};

			const { getChangeHandler } = handlers;
			getChangeHandler( oldGroup )( newGroup );

			expect( mockOnChange.mock.calls.length ).toBe( 1 );
			expect( mockOnChange.mock.calls[ 0 ][ 0 ] ).toStrictEqual( [
				{
					id: '1',
					country: 'US',
					method: 'flat_rate',
					currency: 'USD',
					rate: 0,
					options: {
						free_shipping_threshold: undefined,
					},
				},
				{
					id: '2',
					country: 'AU',
					method: 'flat_rate',
					currency: 'USD',
					rate: 0,
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

		it( 'returns value updated based on removed and added countries', () => {
			const oldGroup = {
				countries: [ 'US', 'AU' ],
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
			};
			// country AU is removed, and country LK is added.
			const newGroup = {
				...oldGroup,
				countries: [ 'US', 'LK' ],
			};

			const { getChangeHandler } = handlers;
			getChangeHandler( oldGroup )( newGroup );

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
					id: '3',
					country: 'CN',
					method: 'flat_rate',
					currency: 'USD',
					rate: 25,
					options: {
						free_shipping_threshold: 50,
					},
				},
				{
					country: 'LK',
					method: 'flat_rate',
					currency: 'USD',
					rate: 20,
					options: {},
				},
			] );
		} );

		it( 'returns value updated based on all changed countries and rate', () => {
			const oldGroup = {
				countries: [ 'US', 'AU' ],
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
			};
			// countries and rate are all changed.
			const newGroup = {
				...oldGroup,
				countries: [ 'VN', 'TH' ],
				rate: 35,
			};

			const { getChangeHandler } = handlers;
			getChangeHandler( oldGroup )( newGroup );

			expect( mockOnChange.mock.calls.length ).toBe( 1 );
			expect( mockOnChange.mock.calls[ 0 ][ 0 ] ).toStrictEqual( [
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
				{
					country: 'VN',
					method: 'flat_rate',
					currency: 'USD',
					rate: 35,
					options: {},
				},
				{
					country: 'TH',
					method: 'flat_rate',
					currency: 'USD',
					rate: 35,
					options: {},
				},
			] );
		} );
	} );

	describe( 'getDeleteHandler', () => {
		it( 'returns value without the deleted group', () => {
			const oldGroup = {
				countries: [ 'US', 'AU' ],
				method: 'flat_rate',
				currency: 'USD',
				rate: 20,
			};

			const { getDeleteHandler } = handlers;
			getDeleteHandler( oldGroup )();

			expect( mockOnChange.mock.calls.length ).toBe( 1 );
			expect( mockOnChange.mock.calls[ 0 ][ 0 ] ).toStrictEqual( [
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
} );
