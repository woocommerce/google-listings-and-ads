/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CyoIncentivePicker from './cyo-incentive-picker';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
} ) );
jest.mock( '~/hooks/useCYOIncentives' );
jest.mock( '~/hooks/useGoogleAdsAccountBillingStatus' );
jest.mock( '~/hooks/useServiceBasedMerchant' );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

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
	const onIncentiveIdChange = jest.fn();

	beforeEach( () => {
		onIncentiveIdChange.mockReset();
		recordGlaEvent.mockReset();
		useAdaptiveFormContext.mockReturnValue( {
			getInputProps: jest.fn().mockReturnValue( {
				value: null,
				onChange: onIncentiveIdChange,
			} ),
		} );

		useCYOIncentives.mockReturnValue( {
			data: INCENTIVES_DATA,
			hasFinishedResolution: true,
		} );

		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: 'approved' },
		} );

		useServiceBasedMerchant.mockReturnValue( false );
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

	it( 'should render the component when incentives are available and billing status is approved', () => {
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: 'approved' },
		} );

		render( <CyoIncentivePicker /> );
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).toBeInTheDocument();
	} );

	it( 'should set incentiveId when selecting an offer', () => {
		render( <CyoIncentivePicker /> );
		const radioButtons = screen.getAllByRole( 'radio' );
		expect( radioButtons ).toHaveLength( 3 );

		fireEvent.click( radioButtons[ 0 ] );
		expect( onIncentiveIdChange ).toHaveBeenCalledWith( '789' );

		fireEvent.click( radioButtons[ 1 ] );
		expect( onIncentiveIdChange ).toHaveBeenCalledWith( '456' );

		fireEvent.click( radioButtons[ 2 ] );
		expect( onIncentiveIdChange ).toHaveBeenCalledWith( '123' );
	} );

	it( 'should track gla_cyo_incentive_picker_shown when rendered', () => {
		render( <CyoIncentivePicker /> );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_picker_shown',
			{ is_service_based_merchant: false }
		);
	} );

	it( 'should track gla_cyo_incentive_picker_shown with isServiceBasedMerchant true', () => {
		useServiceBasedMerchant.mockReturnValue( true );
		render( <CyoIncentivePicker /> );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_picker_shown',
			{ is_service_based_merchant: true }
		);
	} );

	it( 'should not track gla_cyo_incentive_picker_shown when not displayed', () => {
		useCYOIncentives.mockReturnValue( {
			data: null,
			hasFinishedResolution: true,
		} );
		render( <CyoIncentivePicker /> );
		expect( recordGlaEvent ).not.toHaveBeenCalledWith(
			'gla_cyo_incentive_picker_shown',
			expect.anything()
		);
	} );

	it( 'should track gla_cyo_incentive_selected with offer level when selecting a radio', () => {
		render( <CyoIncentivePicker /> );
		const radioButtons = screen.getAllByRole( 'radio' );

		fireEvent.click( radioButtons[ 0 ] );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_selected',
				{ is_service_based_merchant: false, offer: 'low' }
			);

		fireEvent.click( radioButtons[ 1 ] );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_cyo_incentive_selected',
				{ is_service_based_merchant: false, offer: 'medium' }
			);

		fireEvent.click( radioButtons[ 2 ] );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_cyo_incentive_selected',
				{ is_service_based_merchant: false, offer: 'high' }
		);
	} );
} );
