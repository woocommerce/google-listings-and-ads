/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CreateNewRefreshNotice from './create-new-refresh-notice';

describe( 'CreateNewRefreshNotice', () => {
	it.each( [
		[ 'account', 'Refresh the page to see your new account.' ],
		[ 'location', 'Refresh the page to see your new location.' ],
	] )( 'asks to refresh the page to see a new %s', ( type, text ) => {
		render( <CreateNewRefreshNotice type={ type } /> );

		expect(
			screen.getByText( text, { selector: 'p' } )
		).toBeInTheDocument();
	} );
} );
