/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CreateNewContainerButton from './create-new-container-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useRefreshGoogleTagManagerConnection from '../hooks/useRefreshGoogleTagManagerConnection';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '../hooks/useRefreshGoogleTagManagerConnection', () =>
	jest.fn().mockName( 'useRefreshGoogleTagManagerConnection' )
);
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( './create-container-modal', () => {
	const { useState } = jest.requireActual( '@wordpress/element' );

	const MockCreateContainerModal = ( { onCreated, onRequestClose } ) => {
		const [ failure, setFailure ] = useState( null );

		return (
			<div role="dialog" aria-label="Mock create modal">
				<button
					onClick={ () =>
						onCreated().catch( ( error ) =>
							setFailure( error.message )
						)
					}
				>
					Mock created
				</button>
				<button onClick={ onRequestClose }>Mock dismiss</button>
				{ failure && <p>{ failure }</p> }
			</div>
		);
	};

	return { __esModule: true, default: MockCreateContainerModal };
} );

function mockAccount( accountFields ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account: { status: 'incomplete', ...accountFields },
	} );
}

describe( 'CreateNewContainerButton', () => {
	let refreshConnection;

	beforeEach( () => {
		jest.clearAllMocks();

		refreshConnection = jest
			.fn()
			.mockName( 'refreshConnection' )
			.mockResolvedValue( { status: 'connected' } );
		useRefreshGoogleTagManagerConnection.mockReturnValue( {
			refreshConnection,
		} );
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

	it.each( [
		[ 'connected', { status: 'connected' } ],
		[
			'created but not connected',
			{ status: 'incomplete', pendingContainerId: '98765433' },
		],
	] )(
		'refreshes the connection once the container is created, without a failure when the result is %s',
		async ( _, refreshedAccount ) => {
			const user = userEvent.setup();
			mockAccount( { canCreateContainer: true } );
			refreshConnection.mockResolvedValue( refreshedAccount );

			render( <CreateNewContainerButton /> );
			await openModal( user );
			await user.click(
				screen.getByRole( 'button', { name: 'Mock created' } )
			);

			await waitFor( () =>
				expect( refreshConnection ).toHaveBeenCalledTimes( 1 )
			);
			expect(
				screen.queryByText( /couldn't be confirmed/ )
			).not.toBeInTheDocument();
		}
	);

	it.each( [
		[ 'the connection could not be refreshed', undefined ],
		[
			'the refreshed connection reports neither a connected nor a pending container',
			{ status: 'incomplete', id: '6002847391' },
		],
	] )( 'reports a failure to the modal when %s', async ( _, result ) => {
		const user = userEvent.setup();
		mockAccount( { canCreateContainer: true } );
		refreshConnection.mockResolvedValue( result );

		render( <CreateNewContainerButton /> );
		await openModal( user );
		await user.click(
			screen.getByRole( 'button', { name: 'Mock created' } )
		);

		expect(
			await screen.findByText(
				"The container was created, but its connection couldn't be confirmed. Please try again."
			)
		).toBeInTheDocument();
		expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
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
