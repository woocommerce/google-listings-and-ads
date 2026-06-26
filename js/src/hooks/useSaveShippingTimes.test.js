/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSaveShippingTimes from './useSaveShippingTimes';
import useShippingTimes from './useShippingTimes';
import { useAppDispatch } from '~/data';

jest.mock( './useShippingTimes' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );

const primaryTimes = [
	{ countryCode: 'US', time: 3, maxTime: 7 },
	{ countryCode: 'CA', time: 3, maxTime: 7 },
];

const secondaryTime = { countryCode: 'DE', time: 5, maxTime: 10 };

describe( 'useSaveShippingTimes', () => {
	let deleteShippingTimes;
	let upsertShippingTimes;

	beforeEach( () => {
		deleteShippingTimes = jest.fn( () => Promise.resolve() );
		upsertShippingTimes = jest.fn( () => Promise.resolve() );

		useShippingTimes.mockReturnValue( {
			data: [ ...primaryTimes, secondaryTime ],
		} );

		useAppDispatch.mockReturnValue( {
			deleteShippingTimes,
			upsertShippingTimes,
		} );
	} );

	describe( 'excludedCountryCodes (default empty)', () => {
		it( 'deletes countries that exist in old times but not in new times', async () => {
			const { result } = renderHook( () => useSaveShippingTimes() );

			// Save only US — CA and DE are missing and should be deleted.
			await act( async () => {
				await result.current.saveShippingTimes( [
					{ countryCode: 'US', time: 3, maxTime: 7 },
				] );
			} );

			expect( deleteShippingTimes ).toHaveBeenCalledWith(
				expect.arrayContaining( [ 'CA', 'DE' ] )
			);
		} );

		it( 'does not call delete when no countries are removed', async () => {
			const { result } = renderHook( () => useSaveShippingTimes() );

			await act( async () => {
				await result.current.saveShippingTimes( [
					...primaryTimes,
					secondaryTime,
				] );
			} );

			expect( deleteShippingTimes ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'excludedCountryCodes provided', () => {
		it( 'does not delete secondary market country even when absent from shippingTimesToSave', async () => {
			const { result } = renderHook( () => useSaveShippingTimes() );

			// Simulate the primary market save: only primary times passed,
			// DE excluded so it must not be deleted.
			await act( async () => {
				await result.current.saveShippingTimes( primaryTimes, [
					'DE',
				] );
			} );

			expect( deleteShippingTimes ).not.toHaveBeenCalled();
		} );

		it( 'does not upsert secondary market country even when present in shippingTimesToSave with changed values', async () => {
			const { result } = renderHook( () => useSaveShippingTimes() );

			// Include DE with a changed time, but it is excluded so no upsert.
			await act( async () => {
				await result.current.saveShippingTimes(
					[
						...primaryTimes,
						{ countryCode: 'DE', time: 99, maxTime: 99 },
					],
					[ 'DE' ]
				);
			} );

			expect( upsertShippingTimes ).not.toHaveBeenCalledWith(
				expect.objectContaining( {
					countries: expect.arrayContaining( [ 'DE' ] ),
				} )
			);
		} );

		it( 'still deletes primary market countries that were removed', async () => {
			const { result } = renderHook( () => useSaveShippingTimes() );

			// US removed from primary, CA kept, DE excluded.
			await act( async () => {
				await result.current.saveShippingTimes(
					[ { countryCode: 'CA', time: 3, maxTime: 7 } ],
					[ 'DE' ]
				);
			} );

			expect( deleteShippingTimes ).toHaveBeenCalledWith( [ 'US' ] );
			expect( deleteShippingTimes ).not.toHaveBeenCalledWith(
				expect.arrayContaining( [ 'DE' ] )
			);
		} );

		it( 'still upserts changed primary market times', async () => {
			const { result } = renderHook( () => useSaveShippingTimes() );

			const updatedPrimaryTimes = [
				{ countryCode: 'US', time: 4, maxTime: 8 },
				{ countryCode: 'CA', time: 4, maxTime: 8 },
			];

			await act( async () => {
				await result.current.saveShippingTimes( updatedPrimaryTimes, [
					'DE',
				] );
			} );

			expect( upsertShippingTimes ).toHaveBeenCalledWith(
				expect.objectContaining( {
					countries: expect.arrayContaining( [ 'US', 'CA' ] ),
					time: 4,
					maxTime: 8,
				} )
			);
		} );
	} );
} );
