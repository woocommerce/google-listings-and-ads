/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import IncompleteGoogleTagManagerAccountCard from './index';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '../hooks/useGoogleTagManagerContainers', () =>
	jest.fn().mockName( 'useGoogleTagManagerContainers' )
);

/**
 * Mocks `useGoogleTagManagerAccount` (the connection record).
 *
 * @param {Object} account The connection payload to mock.
 */
function mockConnection( account ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account,
		hasFinishedResolution: true,
	} );
}

/**
 * Mocks `useGoogleTagManagerContainers` (the candidate containers list).
 *
 * @param {Object[]} [containers] The containers to mock.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockContainers( containers, hasFinishedResolution = true ) {
	useGoogleTagManagerContainers.mockReturnValue( {
		containers,
		hasFinishedResolution,
	} );
}

describe( 'IncompleteGoogleTagManagerAccountCard', () => {
	let fetchSelectContainer;
	let createNotice;
	let fetchGoogleTagManagerAccount;

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

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		fetchGoogleTagManagerAccount = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerAccount' )
			.mockResolvedValue();
		useAppDispatch.mockReturnValue( { fetchGoogleTagManagerAccount } );

		mockConnection( {
			status: 'incomplete',
			id: '6002847391',
			name: 'Enjoy Mommyhood',
		} );
	} );

	it( 'always shows the "Action needed" badge, no Connect button', () => {
		mockContainers( [] );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
	} );

	it( 'shows the "Action needed" badge immediately, but no detail content until the containers list has resolved', () => {
		mockContainers( undefined, false );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();
	} );

	it( 'auto-selects and shows a non-interactive select-control when exactly one container exists, and saves it', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{
				id: '98765432',
				publicId: 'GTM-PR99HWXX',
				name: 'woo',
			},
		] );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Enjoy Mommyhood' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://tagmanager.google.com/#/accounts/6002847391'
		);
		const containerSelect = screen.getByRole( 'combobox' );
		expect( containerSelect ).toHaveDisplayValue( 'woo (GTM-PR99HWXX)' );
		expect( containerSelect ).toHaveAttribute( 'readonly' );

		const saveButton = screen.getByRole( 'button', { name: 'Save' } );
		expect( saveButton ).toBeEnabled();

		await user.click( saveButton );
		expect( fetchSelectContainer ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );

		expect(
			screen.getByRole( 'link', {
				name: 'Create new container (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', 'https://tagmanager.google.com/' );
	} );

	it( 'shows a container select-control when multiple containers exist', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{
				id: '98765432',
				publicId: 'GTM-PR99HWXX',
				name: 'woo',
			},
			{
				id: '11223344',
				publicId: 'GTM-QQ11WWXX',
				name: 'blog',
			},
		] );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		// Auto-selects the first option, so it's already enabled.
		const saveButton = screen.getByRole( 'button', { name: 'Save' } );
		expect( saveButton ).toBeEnabled();

		await user.selectOptions( screen.getByRole( 'combobox' ), '11223344' );
		await user.click( saveButton );

		expect( fetchSelectContainer ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'shows an error notice and does not refresh the account when the save request fails', async () => {
		const user = userEvent.setup();
		fetchSelectContainer.mockRejectedValue( new Error( 'Request failed' ) );
		mockContainers( [
			{
				id: '98765432',
				publicId: 'GTM-PR99HWXX',
				name: 'woo',
			},
		] );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Unable to select this Google Tag Manager container. Please try again.'
		);
		expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();
	} );
} );
