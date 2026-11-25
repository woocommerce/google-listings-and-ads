/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CheckFailureNotice from './check-failure-notice';

describe( 'CheckFailureNotice', () => {
	it( 'renders nothing when the check did not fail', () => {
		const { container } = render(
			<CheckFailureNotice checkFailure={ null } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'says the post could not be checked', () => {
		render( <CheckFailureNotice checkFailure="error" /> );

		expect(
			screen.getByText(
				'We couldn’t check whether this post still exists on Google Business Profile.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );

	it( 'asks to reconnect the Google account when that is why the check failed', () => {
		render( <CheckFailureNotice checkFailure="reconnect" /> );

		expect(
			screen.getByText(
				'We couldn’t check whether this post still exists on Google Business Profile. Reconnect your Google account to check it.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );
} );
