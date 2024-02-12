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

jest.mock( '.~/hooks/usePolling', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'usePolling' )
		.mockImplementation( () => {
			return {
				start: () => {},
				data: { status: null },
			};
		} ),
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
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import CTA from './index';

describe( 'Enhanced Conversion CTA', () => {
	test( 'When not yet loaded, should render a loading spinner', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			isResolving: false,
			hasFinishedResolution: false,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
			isResolving: false,
		} );

		render( <CTA /> );
		const spinner = screen.getByRole( 'status', { name: 'spinner' } );
		expect( spinner ).toBeInTheDocument();
	} );

	test( 'Prompt the user to sign the TOS', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: false,
			isResolving: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: null,
			isResolving: false,
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
			isResolving: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: 'disabled',
			isResolving: false,
		} );

		render( <CTA enableLabel="Confirm" /> );
		expect( screen.getByText( 'Confirm' ) ).toBeInTheDocument();
	} );

	test( 'Prompt the user to disable enhanced conversion tracking if enabled', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			isResolving: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: 'enabled',
			isResolving: false,
		} );

		render( <CTA disableLabel="Disable tracking" /> );
		expect( screen.getByText( 'Disable tracking' ) ).toBeInTheDocument();
	} );

	test( 'Render a spinner when the status is set to pending', () => {
		useAcceptedCustomerDataTerms.mockReturnValue( {
			acceptedCustomerDataTerms: true,
			isResolving: false,
			hasFinishedResolution: true,
		} );

		useAllowEnhancedConversions.mockReturnValue( {
			allowEnhancedConversions: 'pending',
			isResolving: false,
		} );

		render( <CTA /> );
		const spinner = screen.getByRole( 'status', { name: 'spinner' } );
		expect( spinner ).toBeInTheDocument();
	} );
} );
