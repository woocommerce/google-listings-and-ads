/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useDisplayTaxRate from './useDisplayTaxRate';

jest.mock( '.~/hooks/useStoreCountry', () => jest.fn() );
const useStoreCountry = require( '.~/hooks/useStoreCountry' );

describe( 'useDisplayTaxRate', () => {
	describe( 'when store country is a non-US country', () => {
		beforeEach( () => {
			useStoreCountry.mockReturnValue( { code: 'PL', name: 'Poland' } );
		} );
		test( 'should return `null` when called with `null`', () => {
			const { result } = renderHook( () => useDisplayTaxRate( null ) );

			expect( result.current ).toBe( null );
		} );
		test( "should return `true` when called with an array containing `'US'`", () => {
			const { result } = renderHook( () =>
				useDisplayTaxRate( [ 'PL', 'US', 'SG' ] )
			);

			expect( result.current ).toBe( true );
		} );
		test( "should return `false` when called with an array not containing `'US'`", () => {
			const { result } = renderHook( () =>
				useDisplayTaxRate( [ 'PL', 'TW', 'SG' ] )
			);

			expect( result.current ).toBe( false );
		} );
	} );

	describe( 'when store country is `US`', () => {
		beforeEach( () => {
			useStoreCountry.mockReturnValue( {
				code: 'US',
				name: 'United States (US)',
			} );
		} );
		test( 'should return `true` when called with `null`', () => {
			const { result } = renderHook( () => useDisplayTaxRate( null ) );

			expect( result.current ).toBe( true );
		} );
		test( "should return `true` when called with an array containing `'US'`", () => {
			const { result } = renderHook( () =>
				useDisplayTaxRate( [ 'PL', 'US', 'SG' ] )
			);

			expect( result.current ).toBe( true );
		} );
		test( "should return `true` when called with an array not containing `'US'`", () => {
			const { result } = renderHook( () =>
				useDisplayTaxRate( [ 'PL', 'TW', 'SG' ] )
			);

			expect( result.current ).toBe( true );
		} );
	} );

	describe( 'when store country is unresolved (returns falsy `{code}`)', () => {
		beforeEach( () => {
			useStoreCountry.mockReturnValue( { code: null } );
		} );
		test( 'should return `null` when called with `null`', () => {
			const { result } = renderHook( () => useDisplayTaxRate( null ) );

			expect( result.current ).toBe( null );
		} );
		test( "should return `true` when called with an array containing `'US'`", () => {
			const { result } = renderHook( () =>
				useDisplayTaxRate( [ 'PL', 'US', 'SG' ] )
			);

			expect( result.current ).toBe( true );
		} );
		test( "should return `null` when called with an array not containing `'US'`", () => {
			const { result } = renderHook( () =>
				useDisplayTaxRate( [ 'PL', 'TW', 'SG' ] )
			);

			expect( result.current ).toBe( null );
		} );
	} );
} );
