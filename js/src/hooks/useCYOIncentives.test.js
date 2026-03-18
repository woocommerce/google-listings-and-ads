/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useCYOIncentives from './useCYOIncentives';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

jest.mock( '~/hooks/useAppSelectDispatch' );

describe( 'useCYOIncentives', () => {
	it( 'returns incentives from store selector payload', () => {
		const incentives = [
			{
				id: 123,
				type: 'new_customer_offer',
			},
		];

		useAppSelectDispatch.mockReturnValue( {
			data: incentives,
			hasFinishedResolution: true,
			isResolving: false,
			invalidateResolution: jest.fn(),
		} );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( useAppSelectDispatch ).toHaveBeenCalledWith(
			'getCYOIncentives'
		);
		expect( result.current ).toEqual( incentives );
	} );

	it( 'returns null when incentives are not available', () => {
		useAppSelectDispatch.mockReturnValue( {
			data: null,
			hasFinishedResolution: true,
			isResolving: false,
			invalidateResolution: jest.fn(),
		} );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( result.current ).toBeNull();
	} );
} );
