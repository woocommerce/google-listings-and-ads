/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CreateNewContainerButton from './create-new-container-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( './create-container-modal', () => {
	const MockCreateContainerModal = ( { onCreated, onRequestClose } ) => (
		<div role="dialog" aria-label="Mock create modal">
			<button onClick={ onCreated }>Mock created</button>
			<button onClick={ onRequestClose }>Mock dismiss</button>
		</div>
	);

	return { __esModule: true, default: MockCreateContainerModal };
} );

function mockAccount( accountFields ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account: { status: 'incomplete', ...accountFields },
	} );
}

describe( 'CreateNewContainerButton', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'opens the modal and records the click', async () => {
		const user = userEvent.setup();
		mockAccount( { canCreateContainer: true } );

		render( <CreateNewContainerButton onCreated={ jest.fn() } /> );

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Create new container' } )
		);

		expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_tag_manager_create_container_button_click',
			{ context: 'settings-tag-manager' }
		);
	} );

	it( 'passes onCreated to the modal', async () => {
		const user = userEvent.setup();
		const onCreated = jest.fn();
		mockAccount( { canCreateContainer: true } );

		render( <CreateNewContainerButton onCreated={ onCreated } /> );
		await user.click(
			screen.getByRole( 'button', { name: 'Create new container' } )
		);
		await user.click(
			screen.getByRole( 'button', { name: 'Mock created' } )
		);

		expect( onCreated ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'closes the modal when it is dismissed', async () => {
		const user = userEvent.setup();
		mockAccount( { canCreateContainer: true } );

		render( <CreateNewContainerButton onCreated={ jest.fn() } /> );
		await user.click(
			screen.getByRole( 'button', { name: 'Create new container' } )
		);
		await user.click(
			screen.getByRole( 'button', { name: 'Mock dismiss' } )
		);

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	it( 'renders when the backend does not say whether containers can be created', () => {
		mockAccount( {} );

		render( <CreateNewContainerButton onCreated={ jest.fn() } /> );

		expect(
			screen.getByRole( 'button', { name: 'Create new container' } )
		).toBeInTheDocument();
	} );

	it( 'renders nothing when the connection cannot create containers', () => {
		mockAccount( { canCreateContainer: false } );

		const { container } = render(
			<CreateNewContainerButton onCreated={ jest.fn() } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'keeps an open modal when the connection turns out not to allow creating containers', async () => {
		const user = userEvent.setup();
		mockAccount( { canCreateContainer: true } );

		const { rerender } = render(
			<CreateNewContainerButton onCreated={ jest.fn() } />
		);
		await user.click(
			screen.getByRole( 'button', { name: 'Create new container' } )
		);

		mockAccount( { canCreateContainer: false } );
		rerender( <CreateNewContainerButton onCreated={ jest.fn() } /> );

		expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Create new container' } )
		).not.toBeInTheDocument();
	} );
} );
