/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LinkedAccountsSectionWrapper from './linked-accounts-section-wrapper';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

describe( 'LinkedAccountsSectionWrapper', () => {
	it( 'wraps the description in a <p> so it receives the section paragraph spacing', () => {
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );

		const { container } = render( <LinkedAccountsSectionWrapper /> );

		// The section stylesheet only applies bottom margin to <p> elements in
		// the header, so the description must be wrapped in a <p> to be spaced
		// away from the boxes when the layout stacks on mobile.
		const paragraph = container.querySelector( '.gla-section__header p' );
		expect( paragraph ).toBeInTheDocument();
		expect( paragraph ).toHaveTextContent(
			/required to use this extension/
		);
	} );
} );
