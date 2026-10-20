/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ReadOnlyNotice from './read-only-notice';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);

const message =
	"The Google connection for this store has read-only access to Google Tag Manager, so containers can't be created from here.";

describe( 'ReadOnlyNotice', () => {
	it( 'explains why containers cannot be created when the connection cannot create them', () => {
		useGoogleTagManagerAccount.mockReturnValue( {
			account: { status: 'incomplete', canCreateContainer: false },
		} );

		render( <ReadOnlyNotice /> );

		expect(
			screen.getByText( message, { selector: 'p' } )
		).toBeInTheDocument();
	} );

	it.each( [
		[ 'it can create containers', { canCreateContainer: true } ],
		[ 'the backend does not say', {} ],
	] )( 'renders nothing when %s', ( _, accountFields ) => {
		useGoogleTagManagerAccount.mockReturnValue( {
			account: { status: 'incomplete', ...accountFields },
		} );

		const { container } = render( <ReadOnlyNotice /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
