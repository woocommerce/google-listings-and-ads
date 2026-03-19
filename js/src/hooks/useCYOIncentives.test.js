/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useCYOIncentives from './useCYOIncentives';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

describe( 'useCYOIncentives', () => {
	it( 'returns incentives from store selector payload', () => {
		const incentives = [
			{
				id: 123,
				type: 'ACQUISITION',
				offer: 'high',
				termsAndConditionsUrl: 'https://example.com/terms-1',
				requirements: {
					spend: {
						awardAmount: {
							currencyCode: 'USD',
							units: '1800',
						},
					},
					requiredAmount: {
						currencyCode: 'USD',
						units: '4000',
					},
				},
			},
			{
				id: 456,
				type: 'ACQUISITION',
				offer: 'medium',
				termsAndConditionsUrl: 'https://example.com/terms-2',
				requirements: {
					spend: {
						awardAmount: {
							currencyCode: 'USD',
							units: '1200',
						},
					},
					requiredAmount: {
						currencyCode: 'USD',
						units: '1800',
					},
				},
			},
			{
				id: 789,
				type: 'ACQUISITION',
				offer: 'low',
				termsAndConditionsUrl: 'https://example.com/terms-3',
				requirements: {
					spend: {
						awardAmount: {
							currencyCode: 'USD',
							units: '600',
						},
					},
					requiredAmount: {
						currencyCode: 'USD',
						units: '1200',
					},
				},
			},
		];

		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getCYOIncentives: () => incentives,
				hasFinishedResolution: () => true,
			} ) )
		);

		const { result } = renderHook( () => useCYOIncentives() );

		expect( result.current ).toEqual( {
			data: incentives,
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns payload with null data when incentives are not available', () => {
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getCYOIncentives: () => null,
				hasFinishedResolution: () => false,
			} ) )
		);

		const { result } = renderHook( () => useCYOIncentives() );

		expect( result.current ).toEqual( {
			data: null,
			hasFinishedResolution: false,
		} );
	} );
} );
