/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useCYOIncentives from './useCYOIncentives';
import { STORE_KEY } from '~/data/constants';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

describe( 'useCYOIncentives', () => {
	const incentives = [
		{
			id: 123,
			type: 'ACQUISITION',
			offer: 'high',
			termsAndConditionsUrl: 'https://example.com/terms-1',
			requirement: {
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
			requirement: {
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
			requirement: {
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

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns resolved empty state without calling the incentives selector when billing is not approved', () => {
		const getCYOIncentives = jest.fn();
		const getGoogleAdsAccountBillingStatus = jest
			.fn()
			.mockReturnValue( { status: 'pending' } );
		const select = jest.fn().mockReturnValue( {
			getCYOIncentives,
			getGoogleAdsAccountBillingStatus,
			hasFinishedResolution: jest.fn(),
		} );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( select ).toHaveBeenCalledWith( STORE_KEY );
		expect( getCYOIncentives ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			data: null,
			hasFinishedResolution: true,
		} );
	} );

	it( 'requests selectors from the store and returns incentives', () => {
		const getCYOIncentives = jest.fn().mockReturnValue( incentives );
		const hasFinishedResolution = jest.fn().mockReturnValue( true );
		const getGoogleAdsAccountBillingStatus = jest.fn().mockReturnValue( {
			status: GOOGLE_ADS_BILLING_STATUS.APPROVED,
		} );
		const select = jest.fn().mockReturnValue( {
			getCYOIncentives,
			getGoogleAdsAccountBillingStatus,
			hasFinishedResolution,
		} );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( select ).toHaveBeenCalledWith( STORE_KEY );
		expect( getCYOIncentives ).toHaveBeenCalledTimes( 1 );
		expect( hasFinishedResolution ).toHaveBeenCalledWith(
			'getCYOIncentives'
		);

		expect( result.current ).toEqual( {
			data: incentives,
			hasFinishedResolution: true,
		} );
	} );
} );
