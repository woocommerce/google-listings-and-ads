/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConnectedSuccessNotice from './connected-success-notice';

describe( 'ConnectedSuccessNotice', () => {
	it( 'renders the success message', () => {
		render( <ConnectedSuccessNotice /> );

		expect(
			screen.getByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.',
				{ selector: 'p' }
			)
		).toBeInTheDocument();
	} );

	it( 'renders without a dismiss button', () => {
		render( <ConnectedSuccessNotice /> );

		expect(
			screen.queryByRole( 'button', { name: 'Close' } )
		).not.toBeInTheDocument();
	} );
} );
