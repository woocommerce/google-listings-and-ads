/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import StatusErrorCard from './status-error-card';
import { useAppDispatch } from '~/data';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'StatusErrorCard', () => {
	let fetchGoogleBusinessProfileAccount;

	beforeEach( () => {
		jest.clearAllMocks();
		fetchGoogleBusinessProfileAccount = jest
			.fn()
			.mockName( 'fetchGoogleBusinessProfileAccount' );
		useAppDispatch.mockReturnValue( {
			fetchGoogleBusinessProfileAccount,
		} );
	} );

	it( 'shows the status error notice without a connect button or a not-connected badge', () => {
		render( <StatusErrorCard /> );

		expect(
			screen.getByText(
				"We couldn't load your Google Business Profile connection status.",
				{ selector: 'p' }
			)
		).toBeInTheDocument();
		expect( screen.queryByText( 'Connect' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Not connected' ) ).not.toBeInTheDocument();
	} );

	it( 'refetches the connection status when "Try again" is clicked', async () => {
		const user = userEvent.setup();
		render( <StatusErrorCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		await waitFor( () => {
			expect( fetchGoogleBusinessProfileAccount ).toHaveBeenCalledTimes(
				1
			);
		} );
	} );

	it( 'records the event when "Try again" is clicked', async () => {
		const user = userEvent.setup();
		render( <StatusErrorCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_status_try_again_button_click',
			{ context: 'settings-business-profile' }
		);
	} );
} );
