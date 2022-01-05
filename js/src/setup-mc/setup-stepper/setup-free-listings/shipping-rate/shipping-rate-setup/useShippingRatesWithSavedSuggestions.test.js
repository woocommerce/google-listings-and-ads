/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useShippingRatesWithSavedSuggestions from './useShippingRatesWithSavedSuggestions';
import useShippingRates from '.~/hooks/useShippingRates';
import useShippingRatesSuggestions from './useShippingRatesSuggestions';
import useSaveSuggestions from './useSaveSuggestions';

jest.mock( '.~/hooks/useShippingRates', () => jest.fn() );
jest.mock( './useShippingRatesSuggestions', () => jest.fn() );
jest.mock( './useSaveSuggestions', () => jest.fn() );

const shippingRatesData = [
	{
		country: 'Malaysia',
		country_code: 'MY',
		currency: 'USD',
		rate: '20',
	},
];

const shippingRatesSuggestionsData = [
	{
		country: 'Malaysia',
		country_code: 'MY',
		currency: 'USD',
		rate: 20,
	},
];

describe( 'useShippingRatesWithSavedSuggestions', () => {
	it( 'should save suggestions as shipping rates when initial shipping rates is empty', async () => {
		useShippingRates
			.mockReturnValueOnce( {
				hasFinishedResolution: false,
				data: undefined,
			} )
			.mockReturnValue( {
				hasFinishedResolution: true,
				data: [],
			} );
		useShippingRatesSuggestions
			.mockReturnValueOnce( {
				loading: true,
				data: undefined,
			} )
			.mockReturnValueOnce( {
				loading: true,
				data: undefined,
			} )
			.mockReturnValue( {
				loading: false,
				data: shippingRatesSuggestionsData,
			} );
		const mockSaveSuggestions = jest.fn();
		useSaveSuggestions.mockReturnValue( mockSaveSuggestions );

		const { result, rerender, waitForNextUpdate } = renderHook( () =>
			useShippingRatesWithSavedSuggestions()
		);

		/**
		 * Shipping rates and suggestions are loading.
		 */
		expect( result.current.loading ).toBe( true );
		expect( result.current.data ).toBe( undefined );
		expect( mockSaveSuggestions ).toHaveBeenCalledTimes( 0 );

		/**
		 * Shipping rates are loaded; suggestions are loading.
		 */
		rerender();
		expect( result.current.loading ).toBe( true );
		expect( result.current.data ).toStrictEqual( [] );
		expect( mockSaveSuggestions ).toHaveBeenCalledTimes( 0 );

		/**
		 * Shipping rates and suggestions are loaded,
		 * and saveSuggestions is called.
		 */
		rerender();
		await waitForNextUpdate();
		expect( result.current.loading ).toBe( false );
		expect( result.current.data ).toStrictEqual( [] );
		expect( mockSaveSuggestions ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should not save suggestions as shipping rates when there is an initial shipping rates', async () => {
		useShippingRates
			.mockReturnValueOnce( {
				hasFinishedResolution: false,
				data: undefined,
			} )
			.mockReturnValue( {
				hasFinishedResolution: true,
				data: shippingRatesData,
			} );
		useShippingRatesSuggestions
			.mockReturnValueOnce( {
				loading: true,
				data: undefined,
			} )
			.mockReturnValueOnce( {
				loading: true,
				data: undefined,
			} )
			.mockReturnValue( {
				loading: false,
				data: shippingRatesSuggestionsData,
			} );
		const mockSaveSuggestions = jest.fn();
		useSaveSuggestions.mockReturnValue( mockSaveSuggestions );

		const { result, rerender } = renderHook( () =>
			useShippingRatesWithSavedSuggestions()
		);

		/**
		 * Shipping rates and suggestions are loading.
		 */
		expect( result.current.loading ).toBe( true );
		expect( result.current.data ).toBe( undefined );
		expect( mockSaveSuggestions ).toHaveBeenCalledTimes( 0 );

		// rerender();
		/**
		 * Shipping rates are loaded; suggestions are loading.
		 */
		rerender();
		expect( result.current.loading ).toBe( true );
		expect( result.current.data ).toStrictEqual( shippingRatesData );
		expect( mockSaveSuggestions ).toHaveBeenCalledTimes( 0 );

		/**
		 * Shipping rates and suggestions are loaded,
		 * and saveSuggestions is not called.
		 */
		rerender();
		expect( result.current.loading ).toBe( false );
		expect( result.current.data ).toStrictEqual( shippingRatesData );
		expect( mockSaveSuggestions ).toHaveBeenCalledTimes( 0 );
	} );
} );
