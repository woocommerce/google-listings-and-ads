/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectionErrorNotice from './connection-error-notice';
import { useAppDispatch } from '~/data';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import { recordGlaEvent } from '~/utils/tracks';
import useConnectGoogleBusinessProfileLocation, {
	CONNECTION_ERROR_SLOTS,
} from '../hooks/useConnectGoogleBusinessProfileLocation';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useDetailedErrorBySlots', () =>
	jest.fn().mockName( 'useDetailedErrorBySlots' )
);
jest.mock( '../hooks/useConnectGoogleBusinessProfileLocation', () => {
	const actual = jest.requireActual(
		'../hooks/useConnectGoogleBusinessProfileLocation'
	);
	return {
		__esModule: true,
		...actual,
		default: jest
			.fn()
			.mockName( 'useConnectGoogleBusinessProfileLocation' ),
	};
} );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'ConnectionErrorNotice', () => {
	let clearDetailedErrorBySlots;
	let retryStatusRefresh;

	beforeEach( () => {
		jest.clearAllMocks();
		clearDetailedErrorBySlots = jest
			.fn()
			.mockName( 'clearDetailedErrorBySlots' );
		useAppDispatch.mockReturnValue( { clearDetailedErrorBySlots } );

		retryStatusRefresh = jest.fn().mockName( 'retryStatusRefresh' );
		useConnectGoogleBusinessProfileLocation.mockReturnValue( {
			isConnecting: false,
			retryStatusRefresh,
		} );
	} );

	it( 'renders nothing when there is no connection error', () => {
		useDetailedErrorBySlots.mockReturnValue( [ undefined ] );

		const { container } = render( <ConnectionErrorNotice /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'clears the error and records the event when "Try again" is clicked, for a genuine connect failure', async () => {
		useDetailedErrorBySlots.mockReturnValue( [
			{ error: { refreshFailedAfterConnecting: false } },
		] );
		const user = userEvent.setup();
		render( <ConnectionErrorNotice /> );

		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( clearDetailedErrorBySlots ).toHaveBeenCalledWith(
			CONNECTION_ERROR_SLOTS
		);
		expect( retryStatusRefresh ).not.toHaveBeenCalled();
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_connection_try_again_button_click',
			{ context: 'settings-business-profile' }
		);
	} );

	it( 'retries only the status refresh when "Try again" is clicked after a refresh-only failure', async () => {
		useDetailedErrorBySlots.mockReturnValue( [
			{ error: { refreshFailedAfterConnecting: true } },
		] );
		const user = userEvent.setup();
		render( <ConnectionErrorNotice /> );

		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( retryStatusRefresh ).toHaveBeenCalledTimes( 1 );
		expect( clearDetailedErrorBySlots ).not.toHaveBeenCalled();
	} );
} );
