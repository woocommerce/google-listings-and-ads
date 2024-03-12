jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	Spinner: jest
		.fn( () => <div role="status" aria-label="spinner" /> )
		.mockName( 'Spinner' ),
} ) );

jest.mock( '.~/hooks/useAcceptedCustomerDataTerms', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useAcceptedCustomerDataTerms' ),
} ) );

jest.mock( '.~/hooks/useAllowEnhancedConversions', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useAllowEnhancedConversions' ),
} ) );

jest.mock( '.~/hooks/useAutoCheckEnhancedConversionTOS', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useAutoCheckEnhancedConversionTOS' )
		.mockImplementation( () => {
			return {
				startEnhancedConversionTOSPolling: jest.fn(),
				stopEnhancedConversionTOSPolling: jest.fn(),
			};
		} ),
} ) );

jest.mock( '.~/data/actions', () => ( {
	...jest.requireActual( '.~/data/actions' ),
	updateEnhancedAdsConversionStatus: jest
		.fn()
		.mockName( 'updateEnhancedAdsConversionStatus' )
		.mockImplementation( () => {
			return { type: 'test', response: 'enabled' };
		} ),
} ) );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import CTA from './index';

describe( 'Enhanced Conversion CTA', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'When not yet loaded, should render a loading spinner', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			hasFinishedResolution: false,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
		} );

		render( <CTA /> );
		const spinner = screen.getByRole( 'status', { name: 'spinner' } );
		expect( spinner ).toBeInTheDocument();
	} );

	test( 'Prompt the user to sign the TOS', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
		} );

		render(
			<CTA acceptTermsLabel="Sign terms of service on Google Ads" />
		);
		expect(
			screen.getByText( 'Sign terms of service on Google Ads' )
		).toBeInTheDocument();
	} );

	test( 'Prompt the user to enable enhanced conversion tracking if the TOS has been accepted', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.DISABLED,
		} );

		render( <CTA enableLabel="Confirm" /> );
		expect( screen.getByText( 'Confirm' ) ).toBeInTheDocument();
	} );

	test( 'Prompt the user to disable enhanced conversion tracking if enabled', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.ENABLED,
		} );

		render( <CTA disableLabel="Disable tracking" /> );
		expect( screen.getByText( 'Disable tracking' ) ).toBeInTheDocument();
	} );

	test( 'Render a spinner when the status is set to pending', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.PENDING,
		} );

		render( <CTA /> );
		const spinner = screen.getByRole( 'status', { name: 'spinner' } );
		expect( spinner ).toBeInTheDocument();
	} );

	test( 'Click on accept TOS button callback', () => {
		window.open = jest.fn();

		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
		} );

		render( <CTA /> );

		const button = screen.getByRole( 'button' );
		userEvent.click( button );

		expect( window.open ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'Click on enable/confirm button callback', () => {
		const handleOnEnable = jest.fn().mockName( 'On Enable click' );

		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
		} );

		render( <CTA onEnableClick={ handleOnEnable } /> );

		const button = screen.getByRole( 'button' );
		userEvent.click( button );

		expect( handleOnEnable ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'Confirm/enable button callback should not be called if TOS has not been accepted', () => {
		const handleOnEnable = jest.fn().mockName( 'On Enable click' );

		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.ENABLED,
		} );

		render( <CTA onEnableClick={ handleOnEnable } /> );

		const button = screen.getByRole( 'button' );
		userEvent.click( button );

		expect( handleOnEnable ).not.toHaveBeenCalled();
	} );

	test( 'Click on disable button callback', () => {
		const handleOnDisable = jest.fn().mockName( 'On Disable click' );

		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.ENABLED,
		} );

		render( <CTA onDisableClick={ handleOnDisable } /> );

		const button = screen.getByRole( 'button' );
		userEvent.click( button );

		expect( handleOnDisable ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'Disable button callback should not be called if TOS has not been accepted', () => {
		const handleOnDisable = jest.fn().mockName( 'On Disable click' );

		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.ENABLED,
		} );

		render( <CTA onDisableClick={ handleOnDisable } /> );

		const button = screen.getByRole( 'button' );
		userEvent.click( button );

		expect( handleOnDisable ).not.toHaveBeenCalled();
	} );

	test( 'Should render the enable button if TOS has been accepted and the status is not enabled', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
		} );

		render( <CTA /> );

		expect(
			screen.getByRole( 'button', { name: 'Enable' } )
		).toBeInTheDocument();
	} );
} );
