jest.mock( '.~/hooks/useAcceptedCustomerDataTerms', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useAcceptedCustomerDataTerms' ),
} ) );
jest.mock( '.~/hooks/useAllowEnhancedConversions', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useAllowEnhancedConversions' ),
} ) );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import EnhancedConversion from './index';
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';

describe( 'Enhanced Conversion', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Render the correct text when TOS has not been accepted', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			hasFinishedResolution: true,
		} );
		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
			hasFinishedResolution: true,
		} );

		render( <EnhancedConversion /> );
		expect(
			screen.getByText(
				'Optimize your conversion tracking with Enhanced Conversions'
			)
		).toBeInTheDocument();
	} );

	test( 'Render the correct text when TOS has been accepted', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			hasFinishedResolution: true,
		} );
		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: ENHANCED_ADS_CONVERSION_STATUS.PENDING,
			hasFinishedResolution: true,
		} );

		render( <EnhancedConversion /> );
		expect(
			screen.getByText( 'Your Enhanced Conversions are almost ready' )
		).toBeInTheDocument();
	} );
} );
