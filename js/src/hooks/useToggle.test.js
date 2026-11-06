/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useToggle from '~/hooks/useToggle';

describe( 'useToggle', () => {
	it( 'defaults to false when no initialValue is provided', () => {
		const { result } = renderHook( () => useToggle() );
		const [ value ] = result.current;
		expect( value ).toBe( false );
	} );

	it( 'respects a custom initialValue', () => {
		const { result } = renderHook( () => useToggle( true ) );
		const [ value ] = result.current;
		expect( value ).toBe( true );
	} );

	it( 'toggles from false to true', () => {
		const { result } = renderHook( () => useToggle() );
		act( () => result.current[ 1 ]() );
		expect( result.current[ 0 ] ).toBe( true );
	} );

	it( 'toggles from true to false', () => {
		const { result } = renderHook( () => useToggle( true ) );
		act( () => result.current[ 1 ]() );
		expect( result.current[ 0 ] ).toBe( false );
	} );

	it( 'returns a stable toggle function reference across renders', () => {
		const { result, rerender } = renderHook( () => useToggle() );
		const toggleFirst = result.current[ 1 ];
		rerender();
		expect( result.current[ 1 ] ).toBe( toggleFirst );
	} );

	it( 'keeps the toggle function reference stable after toggling', () => {
		const { result } = renderHook( () => useToggle() );
		const toggleFirst = result.current[ 1 ];
		act( () => result.current[ 1 ]() );
		expect( result.current[ 1 ] ).toBe( toggleFirst );
	} );

	it( 'sets an explicit value with the setter', () => {
		const { result } = renderHook( () => useToggle() );
		act( () => result.current[ 2 ]( true ) );
		expect( result.current[ 0 ] ).toBe( true );
	} );

	it( 'keeps the value when the setter is called repeatedly with the same value', () => {
		const { result } = renderHook( () => useToggle( true ) );
		act( () => {
			result.current[ 2 ]( false );
			result.current[ 2 ]( false );
		} );
		expect( result.current[ 0 ] ).toBe( false );
	} );
} );
