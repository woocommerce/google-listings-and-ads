/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useCYOIncentives from './useCYOIncentives';
import useGoogleAdsAccountBillingStatus from './useGoogleAdsAccountBillingStatus';
import { STORE_KEY } from '~/data/constants';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';

jest.mock( './useGoogleAdsAccountBillingStatus' );

const MOCKED_INCENTIVES = [
	{
		id: '123',
		type: 'ACQUISITION',
		offer: 'high',
		termsAndConditionsUrl: 'https://example.com/terms-1',
		requirement: {
			spend: {
				awardAmount: {
					currencyCode: 'USD',
					units: '1800',
				},
				requiredAmount: {
					currencyCode: 'USD',
					units: '4000',
				},
			},
		},
	},
	{
		id: '456',
		type: 'ACQUISITION',
		offer: 'medium',
		termsAndConditionsUrl: 'https://example.com/terms-2',
		requirement: {
			spend: {
				awardAmount: {
					currencyCode: 'USD',
					units: '1200',
				},
				requiredAmount: {
					currencyCode: 'USD',
					units: '1800',
				},
			},
		},
	},
	{
		id: '789',
		type: 'ACQUISITION',
		offer: 'low',
		termsAndConditionsUrl: 'https://example.com/terms-3',
		requirement: {
			spend: {
				awardAmount: {
					currencyCode: 'USD',
					units: '600',
				},
				requiredAmount: {
					currencyCode: 'USD',
					units: '1200',
				},
			},
		},
	},
];

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

describe( 'useCYOIncentives', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns resolved empty state without calling the incentives selector when billing is not approved', () => {
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: 'pending' },
			hasFinishedResolution: true,
		} );

		const getCYOIncentives = jest.fn();
		const select = jest.fn().mockReturnValue( {
			getCYOIncentives,
			hasFinishedResolution: jest.fn(),
		} );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( select ).not.toHaveBeenCalledWith( STORE_KEY );
		expect( getCYOIncentives ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			data: null,
			hasFinishedResolution: true,
		} );
	} );

	it( 'requests selectors from the store and returns incentives', () => {
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: GOOGLE_ADS_BILLING_STATUS.APPROVED },
		} );

		const getCYOIncentives = jest.fn().mockReturnValue( MOCKED_INCENTIVES );
		const hasFinishedResolution = jest.fn().mockReturnValue( true );
		const select = jest.fn().mockReturnValue( {
			getCYOIncentives,
			hasFinishedResolution,
		} );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( select ).toHaveBeenCalledWith( STORE_KEY );
		expect( getCYOIncentives ).toHaveBeenCalledTimes( 1 );
		expect( hasFinishedResolution ).toHaveBeenCalledWith(
			'getCYOIncentives',
			[]
		);

		expect( result.current ).toEqual( {
			data: MOCKED_INCENTIVES,
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns unresolved empty state when billing status is not yet loaded', () => {
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: undefined,
			hasFinishedResolution: false,
		} );

		const getCYOIncentives = jest.fn();
		const select = jest.fn().mockReturnValue( {
			getCYOIncentives,
			hasFinishedResolution: jest.fn(),
		} );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( select ).not.toHaveBeenCalledWith( STORE_KEY );
		expect( getCYOIncentives ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			data: null,
			hasFinishedResolution: false,
		} );
	} );

	it( 'returns hasFinishedResolution: false while incentives are still loading', () => {
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: GOOGLE_ADS_BILLING_STATUS.APPROVED },
		} );

		const getCYOIncentives = jest.fn().mockReturnValue( null );
		const hasFinishedResolution = jest.fn().mockReturnValue( false );
		const select = jest.fn().mockReturnValue( {
			getCYOIncentives,
			hasFinishedResolution,
		} );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useCYOIncentives() );

		expect( result.current ).toEqual( {
			data: null,
			hasFinishedResolution: false,
		} );
	} );
} );
