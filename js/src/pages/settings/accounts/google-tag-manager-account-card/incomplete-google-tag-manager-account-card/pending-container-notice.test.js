/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import PendingContainerNotice from './pending-container-notice';
import useConnectGoogleTagManagerContainer from '../hooks/useConnectGoogleTagManagerContainer';

jest.mock( '../hooks/useConnectGoogleTagManagerContainer', () =>
	jest.fn().mockName( 'useConnectGoogleTagManagerContainer' )
);

describe( 'PendingContainerNotice', () => {
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

	it( 'explains that the container was created but not connected, with only a "Try again" action', () => {
		render( <PendingContainerNotice containerId="98765433" /> );

		expect(
			screen.getByText(
				"The container was created, but its connection couldn't be confirmed. Please try again.",
				{ selector: 'p' }
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Try again' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
	} );

	it( 'connects the pending container by its ID on "Try again"', async () => {
		const user = userEvent.setup();

		render( <PendingContainerNotice containerId="98765433" /> );
		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( connectContainer ).toHaveBeenCalledTimes( 1 );
		expect( connectContainer ).toHaveBeenCalledWith( '98765433' );
	} );

	it( 'disables "Try again" while the container is being connected', () => {
		useConnectGoogleTagManagerContainer.mockReturnValue( [
			connectContainer,
			{ loading: true },
		] );

		render( <PendingContainerNotice containerId="98765433" /> );

		expect(
			screen.getByRole( 'button', { name: 'Try again' } )
		).toBeDisabled();
	} );
} );
