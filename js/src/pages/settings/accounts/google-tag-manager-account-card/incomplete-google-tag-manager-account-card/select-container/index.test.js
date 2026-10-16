/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import SelectContainer from './index';
import useConnectGoogleTagManagerContainer from '../../hooks/useConnectGoogleTagManagerContainer';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '../../hooks/useConnectGoogleTagManagerContainer', () =>
	jest.fn().mockName( 'useConnectGoogleTagManagerContainer' )
);
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( '../create-new-container-button', () => () => (
	<button>Mock create new container</button>
) );
jest.mock( '../read-only-notice', () => () => <p>Mock read-only notice</p> );
jest.mock( './google-tag-manager-container-select-control', () => {
	const MockSelectControl = ( { onChange } ) => (
		<button onClick={ () => onChange( '98765432' ) }>Mock pick</button>
	);

	return { __esModule: true, default: MockSelectControl };
} );

describe( 'SelectContainer', () => {
	let connectContainer;

	beforeEach( () => {
		jest.clearAllMocks();

		connectContainer = jest
			.fn()
			.mockName( 'connectContainer' )
			.mockResolvedValue();
		useConnectGoogleTagManagerContainer.mockReturnValue( [
			connectContainer,
			{ loading: false },
		] );
	} );

	it( 'disables Save while the container is being connected', async () => {
		const user = userEvent.setup();
		useConnectGoogleTagManagerContainer.mockReturnValue( [
			connectContainer,
			{ loading: true },
		] );

		render( <SelectContainer /> );
		await user.click( screen.getByRole( 'button', { name: 'Mock pick' } ) );

		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeDisabled();
	} );

	it( 'disables Save until a container is picked', async () => {
		const user = userEvent.setup();

		render( <SelectContainer /> );

		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeDisabled();

		await user.click( screen.getByRole( 'button', { name: 'Mock pick' } ) );

		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeEnabled();
	} );

	it( 'connects the picked container on Save', async () => {
		const user = userEvent.setup();

		render( <SelectContainer /> );
		await user.click( screen.getByRole( 'button', { name: 'Mock pick' } ) );
		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( connectContainer ).toHaveBeenCalledTimes( 1 );
		expect( connectContainer ).toHaveBeenCalledWith( '98765432' );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_tag_manager_container_select_button_click',
			{ context: 'settings-tag-manager' }
		);
	} );

	it( 'offers to create a container and explains a read-only connection', () => {
		render( <SelectContainer /> );

		expect(
			screen.getByRole( 'button', {
				name: 'Mock create new container',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Mock read-only notice' )
		).toBeInTheDocument();
	} );
} );
