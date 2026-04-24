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
import { recordGlaEvent } from '~/utils/tracks';
import useAdsCurrency from '~/hooks/useAdsCurrency';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
} ) );
jest.mock( '~/hooks/useCYOIncentives' );
jest.mock( '~/hooks/useGoogleAdsAccountBillingStatus' );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );
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
	const onIncentiveIdChange = jest.fn();
	const onRetry = jest.fn();

	const renderComponent = ( props = {} ) =>
		render(
			<CyoIncentivePicker
				context="setup-mc"
				incentiveResult={ { error: null, loading: false } }
				onRetry={ onRetry }
				{ ...props }
			/>
		);

	beforeEach( () => {
		onIncentiveIdChange.mockReset();
		recordGlaEvent.mockReset();
		onRetry.mockReset();
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
	} );

	it( 'should render the component', () => {
		renderComponent();
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).toBeInTheDocument();
	} );

	it( 'should not render if incentives are not available', () => {
		useCYOIncentives.mockReturnValue( {
			data: null,
			hasFinishedResolution: true,
		} );
		renderComponent();
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();
	} );

	it( 'should not render if incentives array is empty', () => {
		useCYOIncentives.mockReturnValue( {
			data: [],
			hasFinishedResolution: true,
		} );

		renderComponent();
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();
	} );

	it( 'should not render if incentives are still loading', () => {
		useCYOIncentives.mockReturnValue( {
			data: null,
			hasFinishedResolution: false,
		} );

		renderComponent();
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();
	} );

	it( 'should not render if billing status is not approved', () => {
		// useCYOIncentives returns no data when billing is not approved
		useCYOIncentives.mockReturnValue( {
			data: null,
			hasFinishedResolution: true,
		} );

		renderComponent();
		const titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();
	} );

	it( 'should render the component when billing status switches from pending to approved', () => {
		// useCYOIncentives returns no data when billing is not approved
		useCYOIncentives.mockReturnValue( {
			data: null,
			hasFinishedResolution: true,
		} );

		const { rerender } = renderComponent();
		let titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).not.toBeInTheDocument();

		// useCYOIncentives returns data once billing is approved
		useCYOIncentives.mockReturnValue( {
			data: INCENTIVES_DATA,
			hasFinishedResolution: true,
		} );

		rerender(
			<CyoIncentivePicker
				context="setup-mc"
				incentiveResult={ { error: null, loading: false } }
				onRetry={ onRetry }
			/>
		);
		titleElement = screen.queryByText( 'Ads credit offer' );
		expect( titleElement ).toBeInTheDocument();
	} );

	it( 'should set default selected incentive to medium offer', () => {
		useAdaptiveFormContext.mockReturnValue( {
			getInputProps: jest.fn().mockReturnValue( {
				value: 'medium',
				onChange: onIncentiveIdChange,
			} ),
		} );

		renderComponent();

		const radioButtons = screen.getAllByRole( 'radio' );
		expect( radioButtons ).toHaveLength( 3 );

		expect( radioButtons[ 1 ] ).toBeChecked();
	} );

	it( 'should call onChange with the offer level when selecting an offer', () => {
		let selectedOffer = null;
		useAdaptiveFormContext.mockReturnValue( {
			getInputProps: jest.fn().mockImplementation( () => ( {
				value: selectedOffer,
				onChange: ( value ) => {
					selectedOffer = value;
					onIncentiveIdChange( value );
				},
			} ) ),
		} );

		const { rerender } = renderComponent();
		let radioButtons = screen.getAllByRole( 'radio' );
		expect( radioButtons ).toHaveLength( 3 );

		fireEvent.click( radioButtons[ 0 ] );
		expect( onIncentiveIdChange ).toHaveBeenNthCalledWith( 1, 'low' );

		rerender(
			<CyoIncentivePicker
				context="setup-mc"
				incentiveResult={ { error: null, loading: false } }
				onRetry={ onRetry }
			/>
		);
		radioButtons = screen.getAllByRole( 'radio' );
		fireEvent.click( radioButtons[ 1 ] );
		expect( onIncentiveIdChange ).toHaveBeenNthCalledWith( 2, 'medium' );

		rerender(
			<CyoIncentivePicker
				context="setup-mc"
				incentiveResult={ { error: null, loading: false } }
				onRetry={ onRetry }
			/>
		);
		radioButtons = screen.getAllByRole( 'radio' );
		fireEvent.click( radioButtons[ 2 ] );
		expect( onIncentiveIdChange ).toHaveBeenNthCalledWith( 3, 'high' );
	} );

	describe( 'error state', () => {
		it( 'should not show error notice when there is no error', () => {
			renderComponent();
			expect( screen.queryByText( 'Try again' ) ).not.toBeInTheDocument();
		} );

		it( 'should show error notice with the API error message', () => {
			renderComponent( {
				incentiveResult: {
					error: { message: 'Something went wrong' },
					loading: false,
				},
			} );
			expect(
				screen.getByText( 'Something went wrong' )
			).toBeInTheDocument();
		} );

		it( 'should show fallback error message when error has no message', () => {
			renderComponent( {
				incentiveResult: { error: {}, loading: false },
			} );
			expect(
				screen.getByText(
					'There was an issue applying the selected offer. Please try again.'
				)
			).toBeInTheDocument();
		} );

		it( 'should call onRetry with the selected incentive ID when retry button is clicked', () => {
			useAdaptiveFormContext.mockReturnValue( {
				getInputProps: jest.fn().mockReturnValue( {
					value: 456,
					onChange: onIncentiveIdChange,
				} ),
			} );

			renderComponent( {
				incentiveResult: {
					error: { message: 'API error' },
					loading: false,
				},
			} );

			fireEvent.click( screen.getByText( 'Try again' ) );

			expect( onRetry ).toHaveBeenCalledWith( 456 );
		} );

		it( 'should disable the retry button in a loading state when incentiveResult.loading is true', () => {
			renderComponent( {
				incentiveResult: {
					error: { message: 'API error' },
					loading: true,
				},
			} );

			const retryButton = screen.getByText( 'Try again' );
			expect( retryButton ).toHaveAttribute( 'disabled' );
		} );

		it( 'should render the "Apply in Google Ads" link in the error notice', () => {
			renderComponent( {
				incentiveResult: {
					error: { message: 'API error' },
					loading: false,
				},
			} );

			expect(
				screen.getByText( 'Apply in Google Ads' )
			).toBeInTheDocument();
		} );
	} );

	it( 'should track gla_cyo_incentive_picker_shown when rendered', () => {
		render( <CyoIncentivePicker context="setup-mc" /> );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_picker_shown',
			{ context: 'setup-mc' }
		);
	} );

	it( 'should track gla_cyo_incentive_picker_shown with isServiceBasedMerchant true', () => {
		render( <CyoIncentivePicker context="setup-mc" /> );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_picker_shown',
			{ context: 'setup-mc' }
		);
	} );

	it( 'should track gla_cyo_incentive_picker_shown only once when isServiceBasedMerchant resolves after shouldDisplay', () => {
		const { rerender } = renderComponent();

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_picker_shown',
			{ context: 'setup-mc' }
		);

		rerender(
			<CyoIncentivePicker
				context="setup-mc"
				incentiveResult={ { error: null, loading: false } }
				onRetry={ onRetry }
			/>
		);

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should not track gla_cyo_incentive_picker_shown when not displayed', () => {
		useCYOIncentives.mockReturnValue( {
			data: null,
			hasFinishedResolution: true,
		} );
		render( <CyoIncentivePicker context="setup-mc" /> );
		expect( recordGlaEvent ).not.toHaveBeenCalledWith(
			'gla_cyo_incentive_picker_shown',
			expect.anything()
		);
	} );

	it( 'should track gla_cyo_incentive_selected with offer level when selecting a radio', () => {
		render( <CyoIncentivePicker context="setup-mc" /> );
		const radioButtons = screen.getAllByRole( 'radio' );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_picker_shown',
			{ context: 'setup-mc' }
		);

		fireEvent.click( radioButtons[ 0 ] );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_selected',
			{
				context: 'setup-mc',
				level: 'low',
			}
		);

		fireEvent.click( radioButtons[ 1 ] );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_selected',
			{
				context: 'setup-mc',
				level: 'medium',
			}
		);

		fireEvent.click( radioButtons[ 2 ] );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_cyo_incentive_selected',
			{
				context: 'setup-mc',
				level: 'high',
			}
		);
	} );
} );
