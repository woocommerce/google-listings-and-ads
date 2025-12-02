/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { registerStore, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useDetailedErrorBySlots from './useDetailedErrorBySlots';
import reducer from '~/data/reducer';
import { STORE_KEY } from '~/data/constants';
import * as selectors from '~/data/selectors';
import TYPES from '~/data/action-types';

const createError = ( slot, overrides = {} ) => ( {
	message: `Error for ${ slot }`,
	code: `code_${ slot }`,
	...overrides,
	slot,
} );

const actions = {
	receiveDetailedError: ( slot, error ) => ( {
		type: TYPES.RECEIVE_DETAILED_ERROR,
		slot,
		error,
	} ),
	clearDetailedErrorBySlot: ( slot ) => ( {
		type: TYPES.CLEAR_DETAILED_ERROR_BY_SLOT,
		slot,
	} ),
};

registerStore( STORE_KEY, { reducer, selectors, actions } );

describe( 'useDetailedErrorBySlots', () => {
	it( 'returns null when errorSlots is empty array', () => {
		const { result } = renderHook( () => useDetailedErrorBySlots( [] ) );
		expect( result.current ).toBeNull();
	} );

	it( 'returns null when no matching errors in store', () => {
		const { result } = renderHook( () =>
			useDetailedErrorBySlots( [ 'slot_a' ] )
		);
		expect( result.current ).toBeNull();
	} );

	it( 'returns first matching error when multiple slots provided', () => {
		dispatch( STORE_KEY ).receiveDetailedError(
			'slot_b',
			createError( 'slot_b' )
		);
		dispatch( STORE_KEY ).receiveDetailedError(
			'slot_a',
			createError( 'slot_a' )
		);
		const { result } = renderHook( () =>
			useDetailedErrorBySlots( [ 'slot_a', 'slot_b' ] )
		);
		expect( result.current ).toMatchObject( {
			slot: 'slot_b',
			error: {
				code: 'code_slot_b',
				message: 'Error for slot_b',
				slot: 'slot_b',
			},
		} );
	} );

	it( 'updates when errorSlots changes to include different slot', () => {
		dispatch( STORE_KEY ).receiveDetailedError(
			'slot_a',
			createError( 'slot_a' )
		);
		const { result, rerender } = renderHook(
			( props ) => useDetailedErrorBySlots( props ),
			{ initialProps: [ 'slot_a' ] }
		);
		expect( result.current ).toMatchObject( { slot: 'slot_a' } );
		dispatch( STORE_KEY ).receiveDetailedError(
			'slot_b',
			createError( 'slot_b' )
		);
		rerender( [ 'slot_b' ] );
		expect( result.current ).toMatchObject( { slot: 'slot_b' } );
	} );

	it( 'returns null after clearing error for provided slot', () => {
		const errorSpy = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
		dispatch( STORE_KEY ).receiveDetailedError(
			'slot_a',
			createError( 'slot_a' )
		);
		const { result, rerender } = renderHook( () =>
			useDetailedErrorBySlots( [ 'slot_a' ] )
		);
		expect( result.current ).not.toBeNull();
		dispatch( STORE_KEY ).clearDetailedErrorBySlot( 'slot_a' );
		rerender();
		expect( result.current ).toBeNull();
		errorSpy.mockRestore();
	} );
} );
