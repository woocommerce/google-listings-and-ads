/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CreateContainerNotice from './create-container-notice';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);

const readOnlyMessage =
	"The Google connection for this store has read-only access to Google Tag Manager, so containers can't be created from here.";
const permissionDeniedMessage =
	"Your Google account doesn't have permission to create containers in this Google Tag Manager account.";

/**
 * Mocks the connection returned by `useGoogleTagManagerAccount`.
 *
 * @param {Object} accountFields Fields to set on the incomplete connection.
 */
function mockAccount( accountFields ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account: { status: 'incomplete', ...accountFields },
	} );
}

describe( 'CreateContainerNotice', () => {
	it( 'shows the read-only message as an info notice when the connection is read-only', () => {
		mockAccount( {
			canCreateContainer: false,
			createContainerDenied: false,
		} );

		const { container } = render( <CreateContainerNotice /> );

		expect(
			screen.getByText( readOnlyMessage, { selector: 'p' } )
		).toBeInTheDocument();
		expect(
			container.querySelector( '.components-notice.is-info' )
		).toBeInTheDocument();
	} );

	it( 'names the account permission in a warning notice when Tag Manager refused to create a container', () => {
		mockAccount( {
			canCreateContainer: false,
			createContainerDenied: true,
		} );

		const { container } = render( <CreateContainerNotice /> );

		expect(
			screen.getByText( permissionDeniedMessage, { selector: 'p' } )
		).toBeInTheDocument();
		expect( screen.queryByText( readOnlyMessage ) ).not.toBeInTheDocument();
		expect(
			container.querySelector( '.components-notice.is-warning' )
		).toBeInTheDocument();
	} );

	it.each( [
		[ 'it can create containers', { canCreateContainer: true } ],
		[ 'the backend does not say', {} ],
	] )( 'renders nothing when %s', ( _, accountFields ) => {
		mockAccount( accountFields );

		const { container } = render( <CreateContainerNotice /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
