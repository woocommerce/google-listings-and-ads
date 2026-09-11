/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ContainerSelection from './container-selection';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { handleApiError } from '~/utils/handleError';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/utils/handleError', () => ( {
	...jest.requireActual( '~/utils/handleError' ),
	handleApiError: jest.fn(),
} ) );
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '../hooks/useGoogleTagManagerContainers', () =>
	jest.fn().mockName( 'useGoogleTagManagerContainers' )
);

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

describe( 'ContainerSelection', () => {
	let fetchSelectContainer;
	let fetchGoogleTagManagerAccount;

	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleTagManagerAccount.mockReturnValue( {
			account: {
				status: 'incomplete',
				id: '6002847391',
				name: 'Enjoy Mommyhood',
			},
			hasFinishedResolution: true,
		} );

		fetchSelectContainer = jest
			.fn()
			.mockName( 'fetchSelectContainer' )
			.mockResolvedValue();
		useApiFetchCallback.mockReturnValue( [
			fetchSelectContainer,
			{ loading: false },
		] );

		fetchGoogleTagManagerAccount = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerAccount' )
			.mockResolvedValue();
		useAppDispatch.mockReturnValue( { fetchGoogleTagManagerAccount } );
	} );

	it( 'renders nothing until the containers list has resolved', () => {
		mockContainers( undefined, false );

		const { container } = render( <ContainerSelection /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'replaces the selector with the CTA when the account has zero containers', () => {
		mockContainers( [] );

		render( <ContainerSelection /> );

		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();

		expect(
			screen.getByRole( 'link', {
				name: 'Create new container (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', 'https://tagmanager.google.com/' );

		// The Ads-conversion notice sits above this ternary, so it must still show with zero
		// containers, not only once a container list exists.
		expect(
			screen.getByText(
				( _, element ) =>
					element?.tagName === 'P' &&
					/already adds a Google Ads conversion tag/.test(
						element.textContent
					)
			)
		).toBeInTheDocument();
	} );

	it( 'shows the CTA inline beside the selector when the account already has containers', () => {
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		expect( screen.getByRole( 'combobox' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save' } )
		).toBeInTheDocument();

		expect(
			screen.getByRole( 'link', {
				name: 'Create new container (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', 'https://tagmanager.google.com/' );
	} );

	it( 'saves the picked container and refreshes the connection', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( fetchSelectContainer ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( "warns that the plugin's Ads tracking may double-count with a GTM Ads tag", () => {
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		expect(
			screen.getByText(
				( _, element ) =>
					element?.tagName === 'P' &&
					/already adds a Google Ads conversion tag/.test(
						element.textContent
					)
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'use this snippet (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/google-for-woocommerce/faq/#analytics-performance-tracking'
		);
	} );

	it( 'reports the error via handleApiError and does not refresh the account when the save request fails', async () => {
		const user = userEvent.setup();
		const error = new Error( 'Request failed' );
		fetchSelectContainer.mockRejectedValue( error );
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		const fallbackMessage =
			'Unable to select this Google Tag Manager container. Please try again.';

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			undefined,
			fallbackMessage
		);
		expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();

		// The toast is transient, but the selector and Save button stay usable — the
		// failure reason needs to stay visible in the card, not just flash in a toast.
		// Scoped to a `<p>` since `@wordpress/components`' Notice also announces this
		// same text into a document-level a11y-speak live region.
		expect(
			screen.getByText( 'Request failed', { selector: 'p' } )
		).toBeInTheDocument();
	} );

	it( 'falls back to the generic message when the error has no message of its own', async () => {
		const user = userEvent.setup();
		fetchSelectContainer.mockRejectedValue( {} );
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect(
			screen.getByText(
				'Unable to select this Google Tag Manager container. Please try again.',
				{ selector: 'p' }
			)
		).toBeInTheDocument();
	} );

	it( 'clears the persistent error notice once a retry succeeds', async () => {
		const user = userEvent.setup();
		fetchSelectContainer.mockRejectedValueOnce(
			new Error( 'Request failed' )
		);
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect(
			screen.getByText( 'Request failed', { selector: 'p' } )
		).toBeInTheDocument();

		fetchSelectContainer.mockResolvedValue();

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect(
			screen.queryByText( 'Request failed', { selector: 'p' } )
		).not.toBeInTheDocument();
	} );

	it( 'does not show the refresh-page notice before "Create new container" has been clicked', () => {
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		expect(
			screen.queryByText(
				( _, element ) =>
					element?.tagName === 'P' &&
					element.textContent ===
						'Refresh the page to see your new container'
			)
		).not.toBeInTheDocument();
	} );

	it( 'shows the refresh-page notice after clicking "Create new container" from the populated-selector state', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		await user.click(
			screen.getByRole( 'link', {
				name: 'Create new container (opens in a new tab)',
			} )
		);

		expect(
			screen.getByText(
				( _, element ) =>
					element?.tagName === 'P' &&
					element.textContent ===
						'Refresh the page to see your new container'
			)
		).toBeInTheDocument();
		expect( fetchSelectContainer ).not.toHaveBeenCalled();
		expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();
	} );

	it( 'shows the refresh-page notice after clicking "Create new container" from the empty state', async () => {
		const user = userEvent.setup();
		mockContainers( [] );

		render( <ContainerSelection /> );

		await user.click(
			screen.getByRole( 'link', {
				name: 'Create new container (opens in a new tab)',
			} )
		);

		expect(
			screen.getByText(
				( _, element ) =>
					element?.tagName === 'P' &&
					element.textContent ===
						'Refresh the page to see your new container'
			)
		).toBeInTheDocument();
		expect( fetchSelectContainer ).not.toHaveBeenCalled();
		expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();
	} );
} );
