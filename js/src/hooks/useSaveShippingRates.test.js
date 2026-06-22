/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSaveShippingRates from './useSaveShippingRates';
import useShippingRates from './useShippingRates';
import { useAppDispatch } from '~/data';

jest.mock( './useShippingRates' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );

const primaryRates = [
	{ id: 1, country: 'US', rate: 10 },
	{ id: 2, country: 'CA', rate: 10 },
];

const secondaryRate = { id: 3, country: 'DE', rate: 15 };

describe( 'useSaveShippingRates', () => {
	let deleteShippingRates;
	let upsertShippingRates;

	beforeEach( () => {
		deleteShippingRates = jest.fn( () => Promise.resolve() );
		upsertShippingRates = jest.fn( () => Promise.resolve() );

		useShippingRates.mockReturnValue( {
			data: [ ...primaryRates, secondaryRate ],
		} );

		useAppDispatch.mockReturnValue( {
			deleteShippingRates,
			upsertShippingRates,
		} );
	} );

	describe( 'excludedCountryCodes (default empty)', () => {
		it( 'deletes rates that exist in old rates but not in new rates', async () => {
			const { result } = renderHook( () => useSaveShippingRates() );

			// Save only US — CA and DE are missing and should be deleted.
			await act( async () => {
				await result.current.saveShippingRates( [
					{ id: 1, country: 'US', rate: 10 },
				] );
			} );

			expect( deleteShippingRates ).toHaveBeenCalledWith(
				expect.arrayContaining( [ 2, 3 ] )
			);
		} );

		it( 'does not call delete when no countries are removed', async () => {
			const { result } = renderHook( () => useSaveShippingRates() );

			await act( async () => {
				await result.current.saveShippingRates( [
					...primaryRates,
					secondaryRate,
				] );
			} );

			expect( deleteShippingRates ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'excludedCountryCodes provided', () => {
		it( 'does not delete secondary market country even when absent from shippingRatesToSave', async () => {
			const { result } = renderHook( () => useSaveShippingRates() );

			// Simulate primary market save: only primary rates passed,
			// DE excluded so it must not be deleted.
			await act( async () => {
				await result.current.saveShippingRates( primaryRates, [
					'DE',
				] );
			} );

			expect( deleteShippingRates ).not.toHaveBeenCalled();
		} );

		it( 'does not upsert secondary market country even when present in shippingRatesToSave with changed values', async () => {
			const { result } = renderHook( () => useSaveShippingRates() );

			// Include DE with a changed rate, but it is excluded so no upsert.
			await act( async () => {
				await result.current.saveShippingRates(
					[ ...primaryRates, { id: 3, country: 'DE', rate: 99 } ],
					[ 'DE' ]
				);
			} );

			expect( upsertShippingRates ).not.toHaveBeenCalledWith(
				expect.arrayContaining( [
					expect.objectContaining( { country: 'DE' } ),
				] )
			);
		} );

		it( 'still deletes primary market countries that were removed', async () => {
			const { result } = renderHook( () => useSaveShippingRates() );

			// US removed from primary, CA kept, DE excluded.
			await act( async () => {
				await result.current.saveShippingRates(
					[ { id: 2, country: 'CA', rate: 10 } ],
					[ 'DE' ]
				);
			} );

			expect( deleteShippingRates ).toHaveBeenCalledWith( [ 1 ] );
			expect( deleteShippingRates ).not.toHaveBeenCalledWith(
				expect.arrayContaining( [ 3 ] )
			);
		} );

		it( 'still upserts changed primary market rates', async () => {
			const { result } = renderHook( () => useSaveShippingRates() );

			const updatedPrimaryRates = [
				{ id: 1, country: 'US', rate: 20 },
				{ id: 2, country: 'CA', rate: 20 },
			];

			await act( async () => {
				await result.current.saveShippingRates( updatedPrimaryRates, [
					'DE',
				] );
			} );

			expect( upsertShippingRates ).toHaveBeenCalledWith(
				expect.arrayContaining( [
					expect.objectContaining( { country: 'US', rate: 20 } ),
					expect.objectContaining( { country: 'CA', rate: 20 } ),
				] )
			);
		} );
	} );
} );
