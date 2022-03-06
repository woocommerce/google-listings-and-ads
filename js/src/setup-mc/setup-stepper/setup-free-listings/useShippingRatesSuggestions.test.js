/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useShippingRatesSuggestions from './useShippingRatesSuggestions';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useApiFetchEffect from '.~/hooks/useApiFetchEffect';

jest.mock( '.~/hooks/useTargetAudienceFinalCountryCodes', () => jest.fn() );
jest.mock( '.~/hooks/useApiFetchEffect', () => jest.fn() );

describe( 'useShippingRatesSuggestions', () => {
	it( 'should return loading true when it is still loading target audience final country codes', () => {
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			loading: true,
		} );
		useApiFetchEffect.mockReturnValue( {
			loading: true,
		} );

		const { result } = renderHook( () => useShippingRatesSuggestions() );

		expect( result.current.loading ).toBe( true );
		expect( result.current.data ).toBe( undefined );
	} );

	it( 'should return loading true when it is still loading shipping rates suggestions', () => {
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			loading: false,
		} );
		useApiFetchEffect.mockReturnValue( {
			loading: true,
		} );

		const { result } = renderHook( () => useShippingRatesSuggestions() );

		expect( result.current.loading ).toBe( true );
		expect( result.current.data ).toBe( undefined );
	} );

	it( 'should return loading false with data when target audience final country codes and shipping rates suggestions are both loaded', () => {
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			loading: false,
			data: [ 'GB', 'US', 'ES' ],
		} );
		useApiFetchEffect.mockReturnValue( {
			loading: false,
			data: [
				{
					country: 'GB',
					method: 'flat_rate',
					currency: 'US',
					rate: 12,
					options: {},
				},
				{
					country: 'US',
					method: 'flat_rate',
					currency: 'US',
					rate: 10,
					options: {},
				},
			],
		} );

		const { result } = renderHook( () => useShippingRatesSuggestions() );

		expect( result.current.loading ).toBe( false );
		expect( result.current.data ).toStrictEqual( [
			{
				country: 'GB',
				method: 'flat_rate',
				currency: 'US',
				rate: 12,
				options: {},
			},
			{
				country: 'US',
				method: 'flat_rate',
				currency: 'US',
				rate: 10,
				options: {},
			},
		] );
	} );
} );
