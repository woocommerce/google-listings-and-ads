/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useTour from './useTour';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import { useAppDispatch } from '.~/data';

jest.mock( '.~/hooks/useAppSelectDispatch' );
jest.mock( '.~/data', () => ( {
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );

describe( 'useTour', () => {
	let mockResolved;
	let upsertTour;

	beforeEach( () => {
		const tourMap = new Map();

		upsertTour = jest
			.fn( ( nextTour ) => tourMap.set( nextTour.id, nextTour ) )
			.mockName( 'upsertTour' );

		useAppDispatch.mockImplementation( () => ( { upsertTour } ) );

		useAppSelectDispatch.mockImplementation( ( _, id ) => {
			const tour = tourMap.get( id );
			return {
				data: tour || null,
				hasFinishedResolution: tourMap.has( id ),
			};
		} );

		mockResolved = ( id, checked ) => {
			//`checked` = undefined for simulating a tour that does not yet exist.
			const tour = checked === undefined ? null : { id, checked };
			tourMap.set( id, tour );
		};
	} );

	it( 'Before a tour is resolved, `tourChecked` should always be true', () => {
		const { result } = renderHook( () => useTour( '1' ) );

		expect( result.current.tourChecked ).toBe( true );
	} );

	it( 'After a tour is resolved, `tourChecked` should be a boolean value based on the fetched `checked`', () => {
		mockResolved( '1' );
		const { result, rerender } = renderHook( () => useTour( '1' ) );

		expect( result.current.tourChecked ).toBe( false );

		mockResolved( '1', true );
		rerender();

		expect( result.current.tourChecked ).toBe( true );

		mockResolved( '1', false );
		rerender();

		expect( result.current.tourChecked ).toBe( false );
	} );

	it( 'After the given ID is changed, `tourChecked` should be changed accordingly', () => {
		mockResolved( '1', true );
		mockResolved( '2', false );

		const { result, rerender } = renderHook( useTour, {
			initialProps: '1',
		} );

		expect( result.current.tourChecked ).toBe( true );

		rerender( '2' );

		expect( result.current.tourChecked ).toBe( false );
	} );

	it( 'Should call upsertTour according to the established structure', () => {
		const { result } = renderHook( () => useTour( '1' ) );

		result.current.setTourChecked( false );

		expect( upsertTour ).toHaveBeenCalledTimes( 1 );
		expect( upsertTour ).toHaveBeenCalledWith(
			{
				id: '1',
				checked: false,
			},
			true
		);
	} );

	it( 'After calling setTourChecked with a different checked value, it should get the new state of `tourChecked` accordingly', () => {
		mockResolved( '1', false );
		const { result, rerender } = renderHook( () => useTour( '1' ) );

		result.current.setTourChecked( true );
		rerender();

		expect( result.current.tourChecked ).toBe( true );

		result.current.setTourChecked( false );
		rerender();

		expect( result.current.tourChecked ).toBe( false );
	} );

	it( 'When calling setTourChecked for setting the same checked state, it should not call upsertTour internally', () => {
		mockResolved( '1', true );
		const { result } = renderHook( () => useTour( '1' ) );

		result.current.setTourChecked( true );

		expect( upsertTour ).toHaveBeenCalledTimes( 0 );
	} );

	it( "The `tourChecked` state with the same tour ID should be the same across this hook's instances", () => {
		mockResolved( '1', false );
		const hookA = renderHook( () => useTour( '1' ) );
		const hookB = renderHook( () => useTour( '1' ) );

		hookA.result.current.setTourChecked( true );
		hookA.rerender();
		hookB.rerender();

		expect( hookA.result.current.tourChecked ).toBe( true );
		expect( hookB.result.current.tourChecked ).toBe( true );

		hookB.result.current.setTourChecked( false );
		hookA.rerender();
		hookB.rerender();

		expect( hookA.result.current.tourChecked ).toBe( false );
		expect( hookB.result.current.tourChecked ).toBe( false );
	} );
} );
