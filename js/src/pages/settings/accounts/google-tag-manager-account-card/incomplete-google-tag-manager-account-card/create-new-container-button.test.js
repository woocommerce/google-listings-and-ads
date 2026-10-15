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

function mockAccount( accountFields ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account: { status: 'incomplete', ...accountFields },
	} );
}

describe( 'CreateNewContainerButton', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'calls onClick and records the click', async () => {
		const user = userEvent.setup();
		const onClick = jest.fn();
		mockAccount( { canCreateContainer: true } );

		render( <CreateNewContainerButton onClick={ onClick } /> );
		await user.click(
			screen.getByRole( 'button', { name: 'Create new container' } )
		);

		expect( onClick ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_tag_manager_create_container_button_click',
			{ context: 'settings-tag-manager' }
		);
	} );

	it( 'renders when the backend does not say whether containers can be created', () => {
		mockAccount( {} );

		render( <CreateNewContainerButton onClick={ jest.fn() } /> );

		expect(
			screen.getByRole( 'button', { name: 'Create new container' } )
		).toBeInTheDocument();
	} );

	it.each( [
		[
			'the connection cannot create containers',
			{ canCreateContainer: false },
		],
	] )( 'renders nothing when %s', ( _, accountFields ) => {
		mockAccount( accountFields );

		const { container } = render(
			<CreateNewContainerButton onClick={ jest.fn() } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );
