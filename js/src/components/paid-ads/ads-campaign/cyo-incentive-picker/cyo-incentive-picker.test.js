/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CyoIncentivePicker from './cyo-incentive-picker';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';

jest.mock( '~/hooks/useCYOIncentives' );
jest.mock( '~/hooks/useGoogleAdsAccountBillingStatus' );
jest.mock( '~/hooks/useAdsCurrency' );

const formatAmountMock = jest.fn();
useAdsCurrency.mockReturnValue( { formatAmount: formatAmountMock } );

const INCENTIVES_DATA = [
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
				requiredAmount: {
					currencyCode: 'USD',
					units: '4000',
				},
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
				requiredAmount: {
					currencyCode: 'USD',
					units: '1800',
				},
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
				requiredAmount: {
					currencyCode: 'USD',
					units: '1200',
				},
			},
		},
	},
];

describe( 'CyoIncentivePicker Component', () => {
	beforeEach( () => {
		useCYOIncentives.mockReturnValue( {
			data: INCENTIVES_DATA,
			hasFinishedResolution: true,
		} );

		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: GOOGLE_ADS_BILLING_STATUS.APPROVED },
		} );
	} );

	it( 'should render the component', () => {
		render( <CyoIncentivePicker /> );
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).toBeInTheDocument();
	} );

	it( 'should not render if incentives are not available', () => {
		useCYOIncentives.mockReturnValue( {
			data: null,
			hasFinishedResolution: true,
		} );
		render( <CyoIncentivePicker /> );
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();
	} );

	it( 'should not render if incentives array is empty', () => {
		useCYOIncentives.mockReturnValue( {
			data: [],
			hasFinishedResolution: true,
		} );

		render( <CyoIncentivePicker /> );
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();
	} );

	it( 'should not render if incentives are still loading', () => {
		useCYOIncentives.mockReturnValue( {
			data: null,
			hasFinishedResolution: false,
		} );

		render( <CyoIncentivePicker /> );
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();
	} );

	it( 'should not render if billing status is not approved', () => {
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: 'pending' },
		} );

		render( <CyoIncentivePicker /> );
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();
	} );

	it( 'should render the component when billing status switches from pending to approved', () => {
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: 'pending' },
		} );

		const { rerender } = render( <CyoIncentivePicker /> );
		let titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();

		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: GOOGLE_ADS_BILLING_STATUS.APPROVED },
		} );

		rerender( <CyoIncentivePicker /> );
		titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).toBeInTheDocument();
	} );
} );
