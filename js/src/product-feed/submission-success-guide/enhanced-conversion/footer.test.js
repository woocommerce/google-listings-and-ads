jest.mock( '.~/hooks/useAcceptedCustomerDataTerms', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useAcceptedCustomerDataTerms' ),
} ) );

jest.mock( '.~/hooks/useAllowEnhancedConversions', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useAllowEnhancedConversions' ),
} ) );

jest.mock( '.~/hooks/useDispatchCoreNotices', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useDispatchCoreNotices' )
		.mockImplementation( () => {
			return {
				createNotice: jest.fn(),
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
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import Footer from './footer';

describe( 'Enhanced Conversion Footer', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Prompt the user to accept the TOS', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			isResolving: false,
			hasFinishedResolution: true,
		} );
		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
			isResolving: false,
		} );

		render( <Footer /> );

		expect(
			screen.getByText( 'Sign terms of service on Google Ads' )
		).toBeInTheDocument();
	} );

	test( 'Click on enable button callback', () => {
		const handleOnModalClose = jest.fn().mockName( 'On button click' );

		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			isResolving: false,
			hasFinishedResolution: true,
		} );
		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.DISABLED,
			isResolving: false,
		} );

		render( <Footer onModalClose={ handleOnModalClose } /> );

		const button = screen.getByRole( 'button', { name: 'Confirm' } );
		userEvent.click( button );

		expect( handleOnModalClose ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'Click on disable button callback', () => {
		const handleOnModalClose = jest.fn().mockName( 'On button click' );

		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			isResolving: false,
			hasFinishedResolution: true,
		} );
		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.ENABLED,
			isResolving: false,
		} );

		render( <Footer onModalClose={ handleOnModalClose } /> );

		const button = screen.getByRole( 'button', { name: 'Disable' } );
		userEvent.click( button );

		expect( handleOnModalClose ).toHaveBeenCalledTimes( 1 );
	} );
} );
