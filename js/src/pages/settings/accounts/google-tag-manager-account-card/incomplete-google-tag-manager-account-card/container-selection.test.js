/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ContainerSelection from './container-selection';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import { logError } from '~/utils/console';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/utils/console' );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
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
	let fetchGoogleTagManagerSettings;
	let createNotice;

	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleAccount.mockReturnValue( { google: undefined } );

		useGoogleTagManagerAccount.mockReturnValue( {
			account: {
				status: 'incomplete',
				id: '6002847391',
				name: 'Enjoy Mommyhood',
			},
			hasFinishedResolution: true,
		} );

		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: true,
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
		fetchGoogleTagManagerSettings = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerSettings' )
			.mockResolvedValue();
		useAppDispatch.mockReturnValue( {
			fetchGoogleTagManagerAccount,
			fetchGoogleTagManagerSettings,
		} );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
	} );

	it( 'resolves the account link to the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
		mockContainers( [] );

		render( <ContainerSelection /> );

		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Ftagmanager.google.com%2F%23%2Faccounts%2F6002847391&Email=merchant%40example.com'
		);
	} );

	it( 'resolves the "Create new container" link to the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
		mockContainers( [] );

		render( <ContainerSelection /> );

		expect(
			screen.getByRole( 'link', {
				name: 'Create new container (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Ftagmanager.google.com%2F&Email=merchant%40example.com'
		);
	} );

	it( 'renders a loading spinner until the containers list has resolved', () => {
		mockContainers( undefined, false );

		render( <ContainerSelection /> );

		expect( screen.getByRole( 'status' ) ).toBeInTheDocument();
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

	it( 'keeps the Save button disabled through the account refresh, not just the save request', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		let resolveAccountFetch;
		fetchGoogleTagManagerAccount.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveAccountFetch = resolve;
			} )
		);

		render( <ContainerSelection /> );

		const saveButton = screen.getByRole( 'button', { name: 'Save' } );
		await user.click( saveButton );

		// The container-select request has already resolved by this point, but the account
		// refresh that actually flips the card into its connected state is still pending — the
		// button must stay disabled for that whole window, not just for the first request.
		expect( fetchSelectContainer ).toHaveBeenCalledTimes( 1 );
		expect( saveButton ).toBeDisabled();

		resolveAccountFetch();

		await waitFor( () => expect( saveButton ).toBeEnabled() );
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

	it( 'shows the error inline and does not refresh the account when the save request fails', async () => {
		const user = userEvent.setup();
		const error = new Error( 'Request failed' );
		fetchSelectContainer.mockRejectedValue( error );
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();

		// No toast — the selector and Save button stay usable, so the failure reason needs to
		// stay visible in the card, not flash and disappear. Scoped to a `<p>` since
		// `@wordpress/components`' Notice also announces this same text into a document-level
		// a11y-speak live region.
		expect(
			screen.getByText( 'Request failed', { selector: 'p' } )
		).toBeInTheDocument();

		// The inline message alone drops the full error object (status code, response data);
		// still logged to the console for debugging.
		expect( logError ).toHaveBeenCalledWith( error );
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

	it( 'clears the stale error notice as soon as a new attempt starts, not just once it succeeds', async () => {
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

		let resolveRetry;
		fetchSelectContainer.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveRetry = resolve;
			} )
		);

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		// The retry request hasn't resolved yet — the stale error must already be gone.
		expect(
			screen.queryByText( 'Request failed', { selector: 'p' } )
		).not.toBeInTheDocument();

		resolveRetry();
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Save' } )
			).toBeEnabled()
		);
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

	describe( 'when the selected container is checked for a Google Ads conversion tag', () => {
		/**
		 * Mocks the account and settings refetched after saving.
		 *
		 * @param {boolean} adsConversionConflict Whether the account reports a conflict.
		 * @param {boolean} snippetInjectionEnabled Whether the settings report the snippet as enabled.
		 */
		const mockRefetched = (
			adsConversionConflict,
			snippetInjectionEnabled
		) => {
			fetchGoogleTagManagerAccount.mockResolvedValue( {
				account: { status: 'connected', adsConversionConflict },
			} );
			fetchGoogleTagManagerSettings.mockResolvedValue( {
				settings: { snippetInjectionEnabled },
			} );
		};

		const saveContainer = async () => {
			const user = userEvent.setup();
			mockContainers( [
				{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
			] );

			render( <ContainerSelection /> );

			await user.click( screen.getByRole( 'button', { name: 'Save' } ) );
		};

		it( 'refetches the account and the settings after saving', async () => {
			await saveContainer();

			expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
			expect( fetchGoogleTagManagerSettings ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'shows a snackbar that stays until dismissed when a conflict turned the snippet off', async () => {
			mockRefetched( true, false );

			await saveContainer();

			expect( createNotice ).toHaveBeenCalledWith(
				'warning',
				"The Google Tag Manager snippet wasn't added to your site due to a conflict with existing Google Ads tracking.",
				expect.objectContaining( {
					type: 'snackbar',
					explicitDismiss: true,
					actions: [
						expect.objectContaining( { label: 'Learn more' } ),
					],
				} )
			);
		} );

		it( 'records the click and opens the general settings from "Learn more"', async () => {
			const push = jest.fn();
			getHistory.mockReturnValue( { push } );
			mockRefetched( true, false );

			await saveContainer();

			const [ , , { actions } ] = createNotice.mock.calls[ 0 ];
			actions[ 0 ].onClick();

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_tag_manager_ads_conflict_snackbar_learn_more_click',
				{ context: 'settings-tag-manager' }
			);
			expect( push ).toHaveBeenCalledWith(
				expect.stringContaining( 'section=general' )
			);
		} );

		it.each( [
			[ 'no conflict was found', false, true ],
			[ 'the merchant had already turned the snippet on', true, true ],
		] )(
			'shows no snackbar when %s',
			async ( _, adsConversionConflict, snippetInjectionEnabled ) => {
				mockRefetched( adsConversionConflict, snippetInjectionEnabled );

				await saveContainer();

				await waitFor( () => {
					expect( fetchGoogleTagManagerSettings ).toHaveBeenCalled();
				} );
				expect( createNotice ).not.toHaveBeenCalled();
			}
		);

		it( 'shows no snackbar and no save error when the refetches fail', async () => {
			await saveContainer();

			await waitFor( () => {
				expect( fetchGoogleTagManagerSettings ).toHaveBeenCalled();
			} );
			expect( createNotice ).not.toHaveBeenCalled();
			expect( logError ).not.toHaveBeenCalled();
		} );
	} );
} );
