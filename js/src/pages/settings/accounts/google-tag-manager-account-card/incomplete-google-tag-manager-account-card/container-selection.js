/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } from '../constants';
import {
	GOOGLE_TAG_MANAGER_ACCOUNT_STATUS,
	SETTINGS_SECTIONS,
} from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { recordGlaEvent } from '~/utils/tracks';
import { getSettingsUrl } from '~/utils/urls';
import { resolveErrorMessage } from '~/utils/handleError';
import { logError } from '~/utils/console';
import AccountCardTextDetail from '../../account-card-text-detail';
import AppButton from '~/components/app-button';
import AppSpinner from '~/components/app-spinner';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import AccountNameWithLink from '../account-name-with-link';
import AdsConversionDuplicateNotice from '../ads-conversion-duplicate-notice';
import NoticeDetail from '../notice-detail';
import GoogleTagManagerContainerSelectControl from './google-tag-manager-container-select-control';
import CreateNewContainerButton from './create-new-container-button';
import CreateContainerModal from './create-container-modal';
import './container-selection.scss';

/**
 * Clicking on the button to save the selected Google Tag Manager container.
 *
 * @event gla_google_tag_manager_container_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * A Google Tag Manager container has been connected.
 *
 * @event gla_google_tag_manager_container_connected
 * @property {string} context Indicates from which page the container was connected. Possible value: 'settings-tag-manager'.
 * @property {string} gtm_account_id The connected Google Tag Manager account ID.
 */

/**
 * A connected Google Tag Manager container has no public ID, so its snippet can't be injected.
 *
 * @event gla_google_tag_manager_injection_failure
 * @property {string} context Indicates from which page the container was connected. Possible value: 'settings-tag-manager'.
 * @property {string} gtm_account_id The connected Google Tag Manager account ID.
 */

/**
 * Clicking "Learn more" on the snackbar shown when the selected container already contains a
 * Google Ads conversion tag, so the Google Tag Manager snippet wasn't added.
 *
 * @event gla_google_tag_manager_ads_conflict_snackbar_learn_more_click
 * @property {string} context Indicates from which page the link was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Records the container connection, plus an injection failure when the connected container has
 * no public ID. Records nothing unless the refreshed state reports a connected container, so a
 * failed refresh never counts as a connection.
 *
 * @param {Object} [account] The refreshed Google Tag Manager connection state.
 */
function recordConnectionEvents( account ) {
	if ( account?.status !== GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED ) {
		return;
	}

	const eventProps = {
		context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
		gtm_account_id: String( account.id ?? '' ),
	};

	recordGlaEvent( 'gla_google_tag_manager_container_connected', eventProps );

	if ( account.injectionFailed ) {
		recordGlaEvent(
			'gla_google_tag_manager_injection_failure',
			eventProps
		);
	}
}

/**
 * The message shown when a container was created but the connection could not be refreshed.
 *
 * @return {string} The message.
 */
function getCreatedNotConnectedMessage() {
	return __(
		'The container was created, but its connection status could not be loaded. Please try again.',
		'google-listings-and-ads'
	);
}

const READ_ONLY_NOTICE = (
	<div className="gla-google-tag-manager-account-card__container-selection-notice">
		<NoticeDetail
			status="info"
			body={
				<p>
					{ __(
						"The Google connection for this store has read-only access to Google Tag Manager, so containers can't be created from here.",
						'google-listings-and-ads'
					) }
				</p>
			}
		/>
	</div>
);

/**
 * Renders the container-selection detail: the already-connected account, and either a container
 * selector with an explicit "Save" action plus an inline "Create new container" link (one or
 * more containers exist), or the "Create new container" link alone in place of the selector
 * (the account has zero containers — there's nothing to select). Clicking "Create new container"
 * in either state opens a modal that creates and connects a container. When the connection
 * can't create containers, an explanation is shown in place of the link.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 * @fires gla_google_tag_manager_ads_conflict_snackbar_learn_more_click
 * @fires gla_google_tag_manager_container_connected
 * @fires gla_google_tag_manager_injection_failure
 *
 * @return {JSX.Element} The detail, or a loading spinner until the containers list has resolved.
 */
export default function ContainerSelection() {
	const { fetchGoogleTagManagerAccount, fetchGoogleTagManagerSettings } =
		useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { account } = useGoogleTagManagerAccount();
	const { containers, hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();
	const [ containerId, setContainerId ] = useState();
	const [ isCreateModalOpen, setIsCreateModalOpen ] = useState( false );
	const [ hasCreatedContainer, setHasCreatedContainer ] = useState( false );
	const [ isRefreshing, setIsRefreshing ] = useState( false );
	const [ saveError, setSaveError ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ fetchSelectContainer ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/containers`,
		method: 'POST',
		data: {
			id: containerId,
		},
	} );

	if ( ! hasResolvedContainers ) {
		return <AppSpinner />;
	}

	const canCreateContainer = account?.canCreateContainer !== false;

	const handleCreateContainerClick = () => {
		setIsCreateModalOpen( true );
	};

	const handleContainerCreated = () => {
		setHasCreatedContainer( true );
	};

	const createContainerLink =
		canCreateContainer && ! hasCreatedContainer ? (
			<CreateNewContainerButton onClick={ handleCreateContainerClick } />
		) : null;

	const readOnlyNotice = canCreateContainer ? null : READ_ONLY_NOTICE;

	const showAdsConflictSnackbar = () => {
		const handleClick = () => {
			recordGlaEvent(
				'gla_google_tag_manager_ads_conflict_snackbar_learn_more_click',
				{ context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT }
			);
			getHistory().push(
				getSettingsUrl( {
					section: SETTINGS_SECTIONS.GENERAL,
				} )
			);
		};

		createNotice(
			'warning',
			__(
				"The Google Tag Manager snippet wasn't added to your site due to a conflict with existing Google Ads tracking.",
				'google-listings-and-ads'
			),
			{
				type: 'snackbar',
				explicitDismiss: true,
				actions: [
					{
						label: __( 'Learn more', 'google-listings-and-ads' ),
						onClick: handleClick,
					},
				],
			}
		);
	};

	/**
	 * Refreshes the connection and snippet settings after a container was connected, records the
	 * connection and shows the snackbar when a detected Google Ads conflict turned the snippet off.
	 *
	 * @return {Promise<Object|undefined>} Resolves with the refreshed connection, or `undefined`
	 * when it couldn't be loaded.
	 */
	const refreshConnection = async () => {
		const [ accountAction, settingsAction ] = await Promise.all( [
			fetchGoogleTagManagerAccount(),
			fetchGoogleTagManagerSettings(),
		] );

		recordConnectionEvents( accountAction?.account );

		if (
			accountAction?.account?.adsConversionConflict &&
			settingsAction?.settings?.snippetInjectionEnabled === false
		) {
			showAdsConflictSnackbar();
		}

		return accountAction?.account;
	};

	/**
	 * Refreshes the connection of a container that was already created, without creating another.
	 * The card is replaced once the refreshed connection reports the container as connected.
	 *
	 * @return {Promise<void>} Resolves when the refresh completes.
	 */
	const refreshCreatedContainer = async () => {
		setIsRefreshing( true );
		try {
			await refreshConnection();
		} catch ( error ) {
			logError( error );
		} finally {
			setIsRefreshing( false );
		}
	};

	const handleCreateModalClose = () => {
		setIsCreateModalOpen( false );

		if ( hasCreatedContainer ) {
			refreshCreatedContainer();
		}
	};

	const createdContainerNotice =
		hasCreatedContainer && ! isCreateModalOpen ? (
			<div className="gla-google-tag-manager-account-card__container-selection-notice">
				<NoticeDetail
					status="error"
					body={ <p>{ getCreatedNotConnectedMessage() }</p> }
					actions={ [
						<AppButton
							key="try-again"
							disabled={ isRefreshing }
							onClick={ refreshCreatedContainer }
							isLink
						>
							{ __( 'Try again', 'google-listings-and-ads' ) }
						</AppButton>,
					] }
				/>
			</div>
		) : null;

	const handleCreateComplete = async () => {
		const refreshedAccount = await refreshConnection();

		// The container is already created and connected, so a refresh that doesn't report it as
		// connected is reported to the modal to retry the refresh only.
		if (
			refreshedAccount?.status !==
			GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED
		) {
			throw new Error( getCreatedNotConnectedMessage() );
		}

		setIsCreateModalOpen( false );
	};

	/**
	 * Selects the picked container and refreshes the connection and snippet settings.
	 * A save error is shown inline rather than as a snackbar — the selector and Save button stay
	 * usable, so the notice needs to stay put until the next attempt. A detected Google Ads
	 * conflict is shown as a snackbar.
	 * Still logged to the console, since the inline message alone drops the full error object
	 * (status code, response data) that `logError` preserves for debugging.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleSaveClick = async () => {
		setSaveError( null );
		setIsSaving( true );
		try {
			await fetchSelectContainer();
			await refreshConnection();
		} catch ( error ) {
			setSaveError( error );
			logError( error );
		} finally {
			setIsSaving( false );
		}
	};

	return (
		<Flex direction="column" gap={ 4 }>
			<FlexItem>
				<AccountCardTextDetail>
					<AccountNameWithLink account={ account } />
				</AccountCardTextDetail>
			</FlexItem>
			<FlexItem>
				<AdsConversionDuplicateNotice />
			</FlexItem>
			<FlexItem className="gla-google-tag-manager-account-card__container-selection-item">
				{ containers?.length ? (
					<>
						<GoogleTagManagerContainerSelectControl
							label={ __(
								'Container',
								'google-listings-and-ads'
							) }
							value={ containerId }
							onChange={ setContainerId }
						/>
						{ readOnlyNotice }
						{ createdContainerNotice }
						{ saveError && (
							<NoticeDetail
								status="error"
								body={
									<p>
										{ resolveErrorMessage(
											saveError,
											undefined,
											__(
												'Unable to select this Google Tag Manager container. Please try again.',
												'google-listings-and-ads'
											)
										) }
									</p>
								}
							/>
						) }
						<Flex justify="start" gap={ 4 }>
							<AppButton
								eventName="gla_google_tag_manager_container_select_button_click"
								eventProps={ {
									context:
										GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
								} }
								onClick={ handleSaveClick }
								disabled={ ! containerId || isSaving }
								loading={ isSaving }
								isPrimary
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>
							{ createContainerLink }
						</Flex>
					</>
				) : (
					<>
						<span className="gla-google-tag-manager-account-card__container-selection-label">
							{ __( 'Container', 'google-listings-and-ads' ) }
						</span>
						<p className="gla-google-tag-manager-account-card__container-selection-text">
							{ __(
								'No container found',
								'google-listings-and-ads'
							) }
						</p>
						{ readOnlyNotice }
						{ createdContainerNotice }
						{ createContainerLink }
					</>
				) }
			</FlexItem>
			{ isCreateModalOpen && (
				<CreateContainerModal
					onContainerCreated={ handleContainerCreated }
					onCreated={ handleCreateComplete }
					onRequestClose={ handleCreateModalClose }
				/>
			) }
		</Flex>
	);
}
