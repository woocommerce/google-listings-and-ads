/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import SelectContainer from './index';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useRefreshGoogleTagManagerConnection from '../../hooks/useRefreshGoogleTagManagerConnection';
import { handleApiError } from '~/utils/handleError';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '../../hooks/useRefreshGoogleTagManagerConnection', () =>
	jest.fn().mockName( 'useRefreshGoogleTagManagerConnection' )
);
jest.mock( '~/utils/handleError', () => ( {
	...jest.requireActual( '~/utils/handleError' ),
	handleApiError: jest.fn().mockName( 'handleApiError' ),
} ) );
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
	let fetchSelectContainer;
	let refreshConnection;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchSelectContainer = jest
			.fn()
			.mockName( 'fetchSelectContainer' )
			.mockResolvedValue();
		useApiFetchCallback.mockReturnValue( [
			fetchSelectContainer,
			{ loading: false },
		] );

		refreshConnection = jest
			.fn()
			.mockName( 'refreshConnection' )
			.mockResolvedValue( { status: 'connected' } );
		useRefreshGoogleTagManagerConnection.mockReturnValue( {
			refreshConnection,
			loading: false,
		} );
	} );

	it.each( [
		[
			'selecting the container',
			() =>
				useApiFetchCallback.mockReturnValue( [
					fetchSelectContainer,
					{ loading: true },
				] ),
		],
		[
			'refreshing the connection',
			() =>
				useRefreshGoogleTagManagerConnection.mockReturnValue( {
					refreshConnection,
					loading: true,
				} ),
		],
	] )( 'disables Save while %s', async ( _, mockLoading ) => {
		const user = userEvent.setup();
		mockLoading();

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

	it( 'selects the picked container, then refreshes the connection', async () => {
		const user = userEvent.setup();

		render( <SelectContainer /> );
		await user.click( screen.getByRole( 'button', { name: 'Mock pick' } ) );
		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( fetchSelectContainer ).toHaveBeenCalledTimes( 1 );
		await waitFor( () =>
			expect( refreshConnection ).toHaveBeenCalledTimes( 1 )
		);
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_tag_manager_container_select_button_click',
			{ context: 'settings-tag-manager' }
		);
	} );

	it( 'reports the error and does not refresh when the save request fails', async () => {
		const user = userEvent.setup();
		const error = new Error( 'Request failed' );
		fetchSelectContainer.mockRejectedValue( error );

		render( <SelectContainer /> );
		await user.click( screen.getByRole( 'button', { name: 'Mock pick' } ) );
		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		await waitFor( () =>
			expect( handleApiError ).toHaveBeenCalledWith(
				error,
				'There was an error selecting your Google Tag Manager container.'
			)
		);
		expect( refreshConnection ).not.toHaveBeenCalled();
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
