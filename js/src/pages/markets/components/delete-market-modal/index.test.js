/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import DeleteMarketModal from '.';
import { useAppDispatch } from '~/data';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import { handleApiError } from '~/utils/handleError';

jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/hooks/useCountryKeyNameMap' );
jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

const NON_PRIMARY_MARKET = {
	id: 'fr',
	country: 'FR',
	feed_label: 'FR',
	language: 'fr',
	currency: 'EUR',
	shipping_rate: 'flat',
	shipping_time: 'flat',
	free_shipping: null,
};

describe( 'DeleteMarketModal', () => {
	let deleteMarketMock;
	let syncSettingsMock;
	let onRequestCloseMock;
	let invalidateResolutionMock;

	beforeEach( () => {
		deleteMarketMock = jest.fn().mockResolvedValue( undefined );
		syncSettingsMock = jest.fn().mockResolvedValue( undefined );
		onRequestCloseMock = jest.fn();
		invalidateResolutionMock = jest.fn();
		handleApiError.mockClear();
		useAppDispatch.mockReturnValue( {
			deleteMarket: deleteMarketMock,
			syncSettings: syncSettingsMock,
			invalidateResolution: invalidateResolutionMock,
		} );
		useCountryKeyNameMap.mockReturnValue( { FR: 'France' } );
	} );

	afterEach( () => {
		useAppDispatch.mockReset();
		useCountryKeyNameMap.mockReset();
	} );

	test( 'names the market in the body using the country name from the map', () => {
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		expect( screen.getByText( /France/ ) ).toBeInTheDocument();
	} );

	test( 'shows the "Delete market?" confirmation title', () => {
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		expect( screen.getByText( 'Delete market?' ) ).toBeInTheDocument();
	} );

	test( 'Cancel calls onRequestClose without dispatching deleteMarket', async () => {
		const user = userEvent.setup();
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( onRequestCloseMock ).toHaveBeenCalledTimes( 1 );
		expect( deleteMarketMock ).not.toHaveBeenCalled();
	} );

	test( 'Confirm dispatches deleteMarket( market.id ) and closes on success', async () => {
		const user = userEvent.setup();
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Delete market' } )
		);

		await waitFor( () => {
			expect( deleteMarketMock ).toHaveBeenCalledTimes( 1 );
		} );
		expect( deleteMarketMock ).toHaveBeenCalledWith(
			NON_PRIMARY_MARKET.id
		);
		await waitFor( () => {
			expect( onRequestCloseMock ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	test( 'Confirm dispatches syncSettings after a successful delete', async () => {
		const user = userEvent.setup();
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Delete market' } )
		);

		await waitFor( () => {
			expect( syncSettingsMock ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	test( 'a failed sync after a successful delete keeps the modal open, shows an error notice, and still invalidates the target audience', async () => {
		syncSettingsMock.mockRejectedValue( new Error( 'sync failed' ) );

		const user = userEvent.setup();
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		const deleteButton = screen.getByRole( 'button', {
			name: 'Delete market',
		} );
		await user.click( deleteButton );

		await waitFor( () => {
			expect( syncSettingsMock ).toHaveBeenCalledTimes( 1 );
		} );
		expect( onRequestCloseMock ).not.toHaveBeenCalled();
		expect( handleApiError ).toHaveBeenCalledTimes( 1 );
		// The deletion succeeded, so the target audience changed server-side
		// and must refresh even though the sync failed.
		expect( invalidateResolutionMock ).toHaveBeenCalledWith(
			'getTargetAudience',
			[]
		);
		await waitFor( () => {
			expect( deleteButton ).toBeEnabled();
		} );
	} );

	test( 'retrying after a failed sync re-runs only the sync, not the delete', async () => {
		syncSettingsMock.mockRejectedValueOnce( new Error( 'sync failed' ) );

		const user = userEvent.setup();
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		const deleteButton = screen.getByRole( 'button', {
			name: 'Delete market',
		} );
		await user.click( deleteButton );

		await waitFor( () => {
			expect( deleteButton ).toBeEnabled();
		} );

		await user.click( deleteButton );

		await waitFor( () => {
			expect( onRequestCloseMock ).toHaveBeenCalledTimes( 1 );
		} );
		expect( deleteMarketMock ).toHaveBeenCalledTimes( 1 );
		expect( syncSettingsMock ).toHaveBeenCalledTimes( 2 );
		// The invalidation belongs to the deletion, so it does not repeat on
		// the retry click.
		expect( invalidateResolutionMock ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'disables both buttons while the request is in flight', async () => {
		// Hold the promise open so we can observe the in-flight state.
		let resolveDelete;
		deleteMarketMock.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveDelete = resolve;
			} )
		);

		const user = userEvent.setup();
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		const cancelButton = screen.getByRole( 'button', { name: 'Cancel' } );
		const deleteButton = screen.getByRole( 'button', {
			name: 'Delete market',
		} );

		await user.click( deleteButton );

		await waitFor( () => {
			expect( deleteButton ).toBeDisabled();
		} );
		expect( cancelButton ).toBeDisabled();

		resolveDelete();
		await waitFor( () => {
			expect( onRequestCloseMock ).toHaveBeenCalled();
		} );
	} );

	test( 'on error, modal stays open and buttons are re-enabled', async () => {
		deleteMarketMock.mockRejectedValue( new Error( 'boom' ) );

		const user = userEvent.setup();
		render(
			<DeleteMarketModal
				market={ NON_PRIMARY_MARKET }
				onRequestClose={ onRequestCloseMock }
			/>
		);

		const deleteButton = screen.getByRole( 'button', {
			name: 'Delete market',
		} );
		await user.click( deleteButton );

		await waitFor( () => {
			expect( deleteMarketMock ).toHaveBeenCalledTimes( 1 );
		} );
		expect( onRequestCloseMock ).not.toHaveBeenCalled();
		expect( syncSettingsMock ).not.toHaveBeenCalled();
		await waitFor( () => {
			expect( deleteButton ).toBeEnabled();
		} );
		expect(
			screen.getByRole( 'button', { name: 'Cancel' } )
		).toBeEnabled();
	} );
} );
