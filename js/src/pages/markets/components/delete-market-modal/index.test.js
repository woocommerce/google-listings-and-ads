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

jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/hooks/useCountryKeyNameMap' );

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
	let onRequestCloseMock;

	beforeEach( () => {
		deleteMarketMock = jest.fn().mockResolvedValue( undefined );
		onRequestCloseMock = jest.fn();
		useAppDispatch.mockReturnValue( {
			deleteMarket: deleteMarketMock,
			invalidateResolution: jest.fn(),
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

		await user.click( screen.getByRole( 'button', { name: 'Delete' } ) );

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
		const deleteButton = screen.getByRole( 'button', { name: 'Delete' } );

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

		const deleteButton = screen.getByRole( 'button', { name: 'Delete' } );
		await user.click( deleteButton );

		await waitFor( () => {
			expect( deleteMarketMock ).toHaveBeenCalledTimes( 1 );
		} );
		expect( onRequestCloseMock ).not.toHaveBeenCalled();
		await waitFor( () => {
			expect( deleteButton ).toBeEnabled();
		} );
		expect(
			screen.getByRole( 'button', { name: 'Cancel' } )
		).toBeEnabled();
	} );
} );
