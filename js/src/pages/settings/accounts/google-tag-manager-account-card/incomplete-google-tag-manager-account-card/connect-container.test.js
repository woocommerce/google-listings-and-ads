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
import ConnectContainer from './connect-container';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import { handleApiError } from '~/utils/handleError';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/utils/handleError', () => ( {
	...jest.requireActual( '~/utils/handleError' ),
	handleApiError: jest.fn().mockName( 'handleApiError' ),
} ) );
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '../hooks/useGoogleTagManagerContainers', () =>
	jest.fn().mockName( 'useGoogleTagManagerContainers' )
);
jest.mock( './create-new-container-button/create-container-modal', () => {
	const { useState } = jest.requireActual( '@wordpress/element' );

	const MockCreateContainerModal = ( { onCreated, onRequestClose } ) => {
		const [ failure, setFailure ] = useState( null );

		return (
			<div role="dialog" aria-label="Mock create modal">
				<button
					onClick={ () =>
						onCreated().catch( ( error ) =>
							setFailure( error.message )
						)
					}
				>
					Mock created
				</button>
				<button onClick={ onRequestClose }>Mock dismiss</button>
				{ failure && <p>{ failure }</p> }
			</div>
		);
	};

	return { __esModule: true, default: MockCreateContainerModal };
} );

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

/**
 * Returns the names of the container-connection events recorded so far, ignoring the Save
 * button's own click event.
 *
 * @return {string[]} The recorded connection event names.
 */
function getRecordedConnectionEventNames() {
	return recordGlaEvent.mock.calls
		.map( ( [ eventName ] ) => eventName )
		.filter(
			( eventName ) =>
				eventName !==
				'gla_google_tag_manager_container_select_button_click'
		);
}

describe( 'ConnectContainer', () => {
	let fetchSelectContainer;
	let fetchGoogleTagManagerAccount;
	let fetchGoogleTagManagerSettings;
	let createNotice;

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

	it( 'renders a loading spinner until the containers list has resolved', () => {
		mockContainers( undefined, false );

		render( <ConnectContainer /> );

		expect( screen.getByRole( 'status' ) ).toBeInTheDocument();
	} );

	it( 'replaces the selector with the CTA when the account has zero containers', () => {
		mockContainers( [] );

		render( <ConnectContainer /> );

		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();

		expect(
			screen.getByRole( 'button', { name: 'Create new container' } )
		).toBeInTheDocument();
	} );

	it( 'shows the CTA inline beside the selector when the account already has containers', () => {
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ConnectContainer /> );

		expect( screen.getByRole( 'combobox' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save' } )
		).toBeInTheDocument();

		expect(
			screen.getByRole( 'button', { name: 'Create new container' } )
		).toBeInTheDocument();
	} );

	it( 'saves the picked container and refreshes the connection', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ConnectContainer /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( fetchSelectContainer ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'records the container connection once the refreshed account is connected', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );
		fetchGoogleTagManagerAccount.mockResolvedValue( {
			account: {
				status: 'connected',
				id: '6002847391',
				injectionFailed: false,
			},
		} );

		render( <ConnectContainer /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( getRecordedConnectionEventNames() ).toEqual( [
			'gla_google_tag_manager_container_connected',
		] );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_tag_manager_container_connected',
			{
				context: 'settings-tag-manager',
				gtm_account_id: '6002847391',
			}
		);
	} );

	it( 'records an injection failure alongside the connection when the container has no public ID', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );
		fetchGoogleTagManagerAccount.mockResolvedValue( {
			account: {
				status: 'connected',
				id: '6002847391',
				injectionFailed: true,
			},
		} );

		render( <ConnectContainer /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( getRecordedConnectionEventNames() ).toEqual( [
			'gla_google_tag_manager_container_connected',
			'gla_google_tag_manager_injection_failure',
		] );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_tag_manager_injection_failure',
			{
				context: 'settings-tag-manager',
				gtm_account_id: '6002847391',
			}
		);
	} );

	it( 'records no connection events when the account refresh fails', async () => {
		const user = userEvent.setup();
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ConnectContainer /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
		expect( getRecordedConnectionEventNames() ).toEqual( [] );
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

		render( <ConnectContainer /> );

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

	it( 'reports the error and does not refresh the account when the save request fails', async () => {
		const user = userEvent.setup();
		const error = new Error( 'Request failed' );
		fetchSelectContainer.mockRejectedValue( error );
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ConnectContainer /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();
		expect( getRecordedConnectionEventNames() ).toEqual( [] );
		expect( handleApiError ).toHaveBeenCalledTimes( 1 );
		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'There was an error selecting your Google Tag Manager container.'
		);
	} );

	it( 'keeps the Save button usable after a failed save, so the merchant can retry', async () => {
		const user = userEvent.setup();
		fetchSelectContainer.mockRejectedValueOnce(
			new Error( 'Request failed' )
		);
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ConnectContainer /> );

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeEnabled();

		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( fetchSelectContainer ).toHaveBeenCalledTimes( 2 );
		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
	} );

	describe( 'creating a container', () => {
		const populatedContainers = [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		];

		it( 'does not show the modal until "Create new container" is clicked', () => {
			mockContainers( populatedContainers );

			render( <ConnectContainer /> );

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
		} );

		it.each( [
			[ 'populated-selector', populatedContainers ],
			[ 'empty', [] ],
		] )(
			'opens the modal instead of leaving the page from the %s state',
			async ( _, containers ) => {
				const user = userEvent.setup();
				mockContainers( containers );

				render( <ConnectContainer /> );

				expect(
					screen.queryByRole( 'link', {
						name: /Create new container/,
					} )
				).not.toBeInTheDocument();

				await user.click(
					screen.getByRole( 'button', {
						name: 'Create new container',
					} )
				);

				expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
				expect( recordGlaEvent ).toHaveBeenCalledWith(
					'gla_google_tag_manager_create_container_button_click',
					{ context: 'settings-tag-manager' }
				);
				expect( fetchSelectContainer ).not.toHaveBeenCalled();
			}
		);

		it( 'returns to the plain connect-container state when the modal is dismissed', async () => {
			const user = userEvent.setup();
			mockContainers( populatedContainers );

			render( <ConnectContainer /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Create new container' } )
			);
			await user.click(
				screen.getByRole( 'button', { name: 'Mock dismiss' } )
			);

			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
			expect( screen.getByRole( 'combobox' ) ).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', { name: 'Save' } )
			).toBeInTheDocument();
		} );

		it( 'refreshes the connection and records it once the container is created', async () => {
			const user = userEvent.setup();
			mockContainers( populatedContainers );
			fetchGoogleTagManagerAccount.mockResolvedValue( {
				account: { status: 'connected', id: '6002847391' },
			} );

			render( <ConnectContainer /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Create new container' } )
			);
			await user.click(
				screen.getByRole( 'button', { name: 'Mock created' } )
			);

			expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
			expect( fetchGoogleTagManagerSettings ).toHaveBeenCalledTimes( 1 );
			expect( getRecordedConnectionEventNames() ).toEqual( [
				'gla_google_tag_manager_create_container_button_click',
				'gla_google_tag_manager_container_connected',
			] );
		} );

		it( 'keeps the modal open and reports a failure when the connection could not be refreshed after the container was created', async () => {
			const user = userEvent.setup();
			mockContainers( populatedContainers );

			render( <ConnectContainer /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Create new container' } )
			);
			await user.click(
				screen.getByRole( 'button', { name: 'Mock created' } )
			);

			expect(
				await screen.findByText(
					"The container was created, but its connection couldn't be confirmed. Please try again."
				)
			).toBeInTheDocument();
			expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
		} );

		it( 'reports a failure when the refreshed connection is not connected after the container was created', async () => {
			const user = userEvent.setup();
			mockContainers( populatedContainers );
			fetchGoogleTagManagerAccount.mockResolvedValue( {
				account: { status: 'incomplete', id: '6002847391' },
			} );

			render( <ConnectContainer /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Create new container' } )
			);
			await user.click(
				screen.getByRole( 'button', { name: 'Mock created' } )
			);

			expect(
				await screen.findByText(
					"The container was created, but its connection couldn't be confirmed. Please try again."
				)
			).toBeInTheDocument();
			expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
		} );

		it( 'reports no failure when the refreshed connection reports a created container that is not connected, since the card then shows its own notice', async () => {
			const user = userEvent.setup();
			mockContainers( populatedContainers );
			fetchGoogleTagManagerAccount.mockResolvedValue( {
				account: {
					status: 'incomplete',
					id: '6002847391',
					pendingContainerId: '98765433',
				},
			} );

			render( <ConnectContainer /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Create new container' } )
			);
			await user.click(
				screen.getByRole( 'button', { name: 'Mock created' } )
			);

			await waitFor( () =>
				expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes(
					1
				)
			);
			expect(
				screen.queryByText(
					"The container was created, but its connection couldn't be confirmed. Please try again."
				)
			).not.toBeInTheDocument();
		} );

		it( 'does not refresh the connection when the modal is dismissed', async () => {
			const user = userEvent.setup();
			mockContainers( populatedContainers );

			render( <ConnectContainer /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Create new container' } )
			);
			await user.click(
				screen.getByRole( 'button', { name: 'Mock dismiss' } )
			);

			expect( fetchGoogleTagManagerAccount ).not.toHaveBeenCalled();
			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
		} );

		it.each( [
			[ 'populated-selector', populatedContainers ],
			[ 'empty', [] ],
		] )(
			'hides the create action and explains why when access is read-only, from the %s state',
			( _, containers ) => {
				useGoogleTagManagerAccount.mockReturnValue( {
					account: {
						status: 'incomplete',
						id: '6002847391',
						canCreateContainer: false,
					},
					hasFinishedResolution: true,
				} );
				mockContainers( containers );

				render( <ConnectContainer /> );

				expect(
					screen.queryByRole( 'button', {
						name: 'Create new container',
					} )
				).not.toBeInTheDocument();
				expect(
					screen.getByText(
						"The Google connection for this store has read-only access to Google Tag Manager, so containers can't be created from here.",
						{ selector: 'p' }
					)
				).toBeInTheDocument();
			}
		);

		it( 'shows the create action when the connection reports it can create containers', () => {
			useGoogleTagManagerAccount.mockReturnValue( {
				account: {
					status: 'incomplete',
					id: '6002847391',
					canCreateContainer: true,
				},
				hasFinishedResolution: true,
			} );
			mockContainers( populatedContainers );

			render( <ConnectContainer /> );

			expect(
				screen.getByRole( 'button', { name: 'Create new container' } )
			).toBeInTheDocument();
			expect(
				screen.queryByText( /read-only access/, { selector: 'p' } )
			).not.toBeInTheDocument();
		} );
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

			render( <ConnectContainer /> );

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

		it( 'shows no snackbar and reports no error when the refetches fail', async () => {
			await saveContainer();

			await waitFor( () => {
				expect( fetchGoogleTagManagerSettings ).toHaveBeenCalled();
			} );
			expect( createNotice ).not.toHaveBeenCalled();
			expect( handleApiError ).not.toHaveBeenCalled();
		} );
	} );
} );
