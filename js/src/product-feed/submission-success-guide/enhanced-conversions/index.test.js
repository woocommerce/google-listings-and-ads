jest.mock( '.~/hooks/useAcceptedCustomerDataTerms', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useAcceptedCustomerDataTerms' ),
} ) );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import EnhancedConversion from './index';

describe( 'Enhanced Conversion', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Render the correct text when TOS has not been accepted', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
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

		render( <EnhancedConversion /> );
		expect(
			screen.getByText( 'Your Enhanced Conversions are almost ready' )
		).toBeInTheDocument();
	} );
} );
