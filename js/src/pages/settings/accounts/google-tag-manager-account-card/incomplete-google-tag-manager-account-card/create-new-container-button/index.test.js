/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CreateNewContainerButton from './index';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( './create-container-modal', () => ( { onRequestClose } ) => (
	<div role="dialog" aria-label="Mock create modal">
		<button onClick={ onRequestClose }>Mock dismiss</button>
	</div>
) );

function mockAccount( accountFields ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account: { status: 'incomplete', ...accountFields },
	} );
}

describe( 'CreateNewContainerButton', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	async function openModal( user ) {
		await user.click(
			screen.getByRole( 'button', { name: 'Create new container' } )
		);
	}

	it( 'opens the modal and records the click', async () => {
		const user = userEvent.setup();
		mockAccount( { canCreateContainer: true } );

		render( <CreateNewContainerButton /> );

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

		await openModal( user );

		expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_tag_manager_create_container_button_click',
			{ context: 'settings-tag-manager' }
		);
	} );

	it( 'closes the modal when it is dismissed', async () => {
		const user = userEvent.setup();
		mockAccount( { canCreateContainer: true } );

		render( <CreateNewContainerButton /> );
		await openModal( user );
		await user.click(
			screen.getByRole( 'button', { name: 'Mock dismiss' } )
		);

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	it( 'renders when the backend does not say whether containers can be created', () => {
		mockAccount( {} );

		render( <CreateNewContainerButton /> );

		expect(
			screen.getByRole( 'button', { name: 'Create new container' } )
		).toBeInTheDocument();
	} );

	it( 'renders nothing when the connection cannot create containers', () => {
		mockAccount( { canCreateContainer: false } );

		const { container } = render( <CreateNewContainerButton /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'removes an open modal along with the button when the connection turns out not to allow creating containers', async () => {
		const user = userEvent.setup();
		mockAccount( { canCreateContainer: true } );

		const { rerender } = render( <CreateNewContainerButton /> );
		await openModal( user );

		mockAccount( { canCreateContainer: false } );
		rerender( <CreateNewContainerButton /> );

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Create new container' } )
		).not.toBeInTheDocument();
	} );
} );
