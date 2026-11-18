jest.mock( '~/hooks/useAppSelectDispatch' );
jest.mock( '~/hooks/useTargetAudience' );

/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import useTargetAudience from '~/hooks/useTargetAudience';
import useHasEuTargetCountry from '~/hooks/useHasEuTargetCountry';

describe( 'useHasEuTargetCountry', () => {
	function mockData( { countries, hasFinishedResolution = true } ) {
		useAppSelectDispatch.mockReturnValue( {
			data: { continents: { EU: { countries: [ 'DE', 'FR' ] } } },
			hasFinishedResolution,
		} );
		useTargetAudience.mockReturnValue( {
			data: hasFinishedResolution ? { countries } : null,
			hasFinishedResolution,
		} );
	}

	test( 'Returns true when a target country is in the EU', () => {
		mockData( { countries: [ 'US', 'DE' ] } );

		const { result } = renderHook( () => useHasEuTargetCountry() );

		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			hasEuTargetCountry: true,
		} );
	} );

	test( 'Returns false when no target country is in the EU', () => {
		mockData( { countries: [ 'US', 'GB' ] } );

		const { result } = renderHook( () => useHasEuTargetCountry() );

		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			hasEuTargetCountry: false,
		} );
	} );

	test.each( [
		[ 'countries is null', { countries: null } ],
		[ 'countries is not an array', { countries: 'DE' } ],
		[ 'the target audience failed to load', null ],
	] )( 'Returns false without throwing when %s', ( _, targetAudience ) => {
		mockData( {} );
		useTargetAudience.mockReturnValue( {
			data: targetAudience,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useHasEuTargetCountry() );

		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			hasEuTargetCountry: false,
		} );
	} );

	test( 'Returns not ready while the data is still loading', () => {
		mockData( { hasFinishedResolution: false } );

		const { result } = renderHook( () => useHasEuTargetCountry() );

		expect( result.current ).toEqual( {
			hasFinishedResolution: false,
			hasEuTargetCountry: false,
		} );
	} );
} );
