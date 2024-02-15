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
import EnhancedConversionFooter from './EnhancedConversionFooter';

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

		render( <EnhancedConversionFooter /> );

		expect(
			screen.getByText( 'Sign terms of service on Google Ads' )
		).toBeInTheDocument();
	} );

	test( 'Click on enable/disable button callback', () => {
		const handleGuideFinish = jest.fn().mockName( 'On button click' );

		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			isResolving: false,
			hasFinishedResolution: true,
		} );
		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.DISABLED,
			isResolving: false,
		} );

		render(
			<EnhancedConversionFooter handleGuideFinish={ handleGuideFinish } />
		);

		const button = screen.getByRole( 'button', { name: 'Confirm' } );
		userEvent.click( button );

		expect( handleGuideFinish ).toHaveBeenCalledTimes( 1 );
	} );
} );
