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
import EnhancedConversion from './EnhancedConversion';

describe( 'Enhanced Conversion', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Render the correct text when TOS has not been accepted', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			isResolving: false,
			hasFinishedResolution: true,
		} );

		render( <EnhancedConversion /> );
		expect(
			screen.getByText(
				'Activating it is easy – just agree to the terms of service on Google Ads and we will make the tagging changes needed for you. This feature can also be managed from Google Listings & Ads > Settings'
			)
		).toBeInTheDocument();
	} );

	test( 'Render the correct text when TOS has been accepted', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			isResolving: false,
			hasFinishedResolution: true,
		} );

		render( <EnhancedConversion /> );
		expect(
			screen.getByText(
				'Clicking confirm will enable Enhanced Conversions on your account and update your tags accordingly. This feature can also be managed from Google Listings & Ads > Settings'
			)
		).toBeInTheDocument();
	} );
} );
