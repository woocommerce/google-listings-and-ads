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
				type: 'ACQUISITION',
                offer: 'high',
                termsAndConditionsUrl: 'https://example.com/terms-1',
                requirements: {
                    spend: {
                        awardAmount: {
                            currencyCode: 'USD',
                            units: '1800',
                        }
                    },
                    requiredAmount: {
                        currencyCode: 'USD',
                        units: '4000',
                    }
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
                        }
                    },
                    requiredAmount: {
                        currencyCode: 'USD',
                        units: '1800',
                    }
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
                        }
                    },
                    requiredAmount: {
                        currencyCode: 'USD',
                        units: '1200',
                    }
                },
			},

		];
		const invalidateResolution = jest.fn();

		useAppSelectDispatch.mockReturnValue( {
			data: {
                type: 'CYO_INCENTIVE',
                termsAndConditionsUrl: 'https://ads.google.com/terms',
				incentives,
			},
			hasFinishedResolution: true,
			isResolving: false,
			invalidateResolution,
		} );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( useAppSelectDispatch ).toHaveBeenCalledWith(
			'getCYOIncentives'
		);
		expect( result.current ).toEqual( {
			data: incentives,
			hasFinishedResolution: true,
			isResolving: false,
			invalidateResolution,
		} );
	} );

	it( 'returns payload with null data when incentives are not available', () => {
		const invalidateResolution = jest.fn();

		useAppSelectDispatch.mockReturnValue( {
			data: null,
			hasFinishedResolution: true,
			isResolving: false,
			invalidateResolution,
		} );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( result.current ).toEqual( {
			data: null,
			hasFinishedResolution: true,
			isResolving: false,
			invalidateResolution,
		} );
	} );
} );
