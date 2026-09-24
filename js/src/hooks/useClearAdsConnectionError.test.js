/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useClearAdsConnectionError from './useClearAdsConnectionError';
import { useAppDispatch } from '~/data';
import { ERROR_SLOTS } from '~/data/constants';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'useClearAdsConnectionError', () => {
	it( 'clears the Google Ads connection error slot when called', () => {
		const clearDetailedErrorBySlots = jest.fn();
		useAppDispatch.mockReturnValue( { clearDetailedErrorBySlots } );

		const { result } = renderHook( () => useClearAdsConnectionError() );
		result.current();

		expect( clearDetailedErrorBySlots ).toHaveBeenCalledWith( [
			ERROR_SLOTS.GOOGLE_ADS_CONNECTION_ERROR_SLOT,
		] );
	} );
} );
