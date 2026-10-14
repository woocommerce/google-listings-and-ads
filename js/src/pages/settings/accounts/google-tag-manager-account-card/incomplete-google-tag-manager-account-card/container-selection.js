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
import { handleApiError } from '~/utils/handleError';
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
import ReadOnlyNotice from './read-only-notice';
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

const CREATED_NOT_CONNECTED_MESSAGE = __(
	"The container was created, but its connection couldn't be confirmed. Please try again.",
	'google-listings-and-ads'
);

/**
 * Renders the container-selection detail: the already-connected account, and either a container
 * selector with an explicit "Save" action plus an inline "Create new container" link (one or
 * more containers exist), or the "Create new container" link alone in place of the selector
 * (the account has zero containers — there's nothing to select). Clicking "Create new container"
 * in either state opens a modal that creates and connects a container. When the connection
 * can't create containers, an explanation is shown in place of the link, and when a container
 * was created but couldn't be connected, a notice offers to try again.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 * @fires gla_google_tag_manager_ads_conflict_snackbar_learn_more_click
 * @fires gla_google_tag_manager_container_connected
 * @fires gla_google_tag_manager_injection_failure
 *
 * @return {JSX.Element} The detail, or a loading spinner until the account and the containers list have resolved.
 */
export default function ContainerSelection() {
	const { fetchGoogleTagManagerAccount, fetchGoogleTagManagerSettings } =
		useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { account, hasFinishedResolution: hasResolvedAccount } =
		useGoogleTagManagerAccount();
	const { containers, hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();
	const [ containerId, setContainerId ] = useState();
	const [ isCreateModalOpen, setIsCreateModalOpen ] = useState( false );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ fetchSelectContainer ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/containers`,
		method: 'POST',
		data: {
			id: containerId,
		},
	} );

	if ( ! hasResolvedAccount || ! hasResolvedContainers ) {
		return <AppSpinner />;
	}

	const openCreateModal = () => {
		setIsCreateModalOpen( true );
	};

	const closeCreateModal = () => {
		setIsCreateModalOpen( false );
	};

	const createdNotConnectedNotice = account?.pendingContainerId ? (
		<div className="gla-google-tag-manager-account-card__container-selection-notice">
			<NoticeDetail
				status="error"
				body={ <p>{ CREATED_NOT_CONNECTED_MESSAGE }</p> }
				actions={ [
					<AppButton
						key="try-again"
						onClick={ openCreateModal }
						isLink
					>
						{ __( 'Try again', 'google-listings-and-ads' ) }
					</AppButton>,
				] }
			/>
		</div>
	) : null;

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
	 * The refreshed connection is returned because the `account` of this render still holds the
	 * state from before the refresh.
	 *
	 * @return {Promise<Object|undefined>} Resolves with the refreshed connection, or `undefined`
	 * when it couldn't be loaded.
	 */
	const refreshConnection = async () => {
		const [ accountAction, settingsAction ] = await Promise.all( [
			fetchGoogleTagManagerAccount(),
			fetchGoogleTagManagerSettings(),
		] );
		const refreshedAccount = accountAction?.account;

		// Nothing is recorded unless the refreshed state reports a connected container, so a failed
		// refresh never counts as a connection.
		if (
			refreshedAccount?.status ===
			GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED
		) {
			const eventProps = {
				context: GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
				gtm_account_id: String( refreshedAccount.id ?? '' ),
			};

			recordGlaEvent(
				'gla_google_tag_manager_container_connected',
				eventProps
			);

			if ( refreshedAccount.injectionFailed ) {
				recordGlaEvent(
					'gla_google_tag_manager_injection_failure',
					eventProps
				);
			}
		}

		if (
			refreshedAccount?.adsConversionConflict &&
			settingsAction?.settings?.snippetInjectionEnabled === false
		) {
			showAdsConflictSnackbar();
		}

		return refreshedAccount;
	};

	const handleCreateComplete = async () => {
		const refreshedAccount = await refreshConnection();

		// The container is already created, so a refresh that doesn't report it as connected is
		// reported to the modal, which retries the refresh only.
		if (
			refreshedAccount?.status !==
			GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED
		) {
			throw new Error( CREATED_NOT_CONNECTED_MESSAGE );
		}

		closeCreateModal();
	};

	/**
	 * Selects the picked container and refreshes the connection and snippet settings.
	 * A detected Google Ads conflict is shown as a snackbar.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleSaveClick = async () => {
		setIsSaving( true );
		try {
			await fetchSelectContainer();
			await refreshConnection();
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error selecting your Google Tag Manager container.',
					'google-listings-and-ads'
				)
			);
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
			<FlexItem>
				<Flex direction="column" gap={ 4 }>
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
							<ReadOnlyNotice />
							{ createdNotConnectedNotice }
							<Flex justify="start" gap={ 4 }>
								<AppButton
									eventName="gla_google_tag_manager_container_select_button_click"
									eventProps={ {
										context:
											GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT,
									} }
									onClick={ handleSaveClick }
									disabled={ ! containerId }
									loading={ isSaving }
									isPrimary
								>
									{ __( 'Save', 'google-listings-and-ads' ) }
								</AppButton>
								<CreateNewContainerButton
									onClick={ openCreateModal }
								/>
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
							<ReadOnlyNotice />
							{ createdNotConnectedNotice }
							<CreateNewContainerButton
								onClick={ openCreateModal }
							/>
						</>
					) }
				</Flex>
			</FlexItem>
			{ isCreateModalOpen && (
				<CreateContainerModal
					onCreated={ handleCreateComplete }
					onRequestClose={ closeCreateModal }
				/>
			) }
		</Flex>
	);
}
