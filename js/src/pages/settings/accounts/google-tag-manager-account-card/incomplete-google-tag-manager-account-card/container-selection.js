/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { resolveErrorMessage } from '~/utils/handleError';
import { logError } from '~/utils/console';
import { recordGlaEvent } from '~/utils/tracks';
import AccountCardTextDetail from '../../account-card-text-detail';
import AppButton from '~/components/app-button';
import AppSpinner from '~/components/app-spinner';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import AccountNameWithLink from '../account-name-with-link';
import AdsConversionDuplicateNotice from '../ads-conversion-duplicate-notice';
import NoticeDetail from '../notice-detail';
import GoogleTagManagerContainerSelectControl from './google-tag-manager-container-select-control';
import CreateNewContainerLink from './create-new-container-link';
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

const SAVE_ERROR_MESSAGE = __(
	'Unable to select this Google Tag Manager container. Please try again.',
	'google-listings-and-ads'
);

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
		context: 'settings-tag-manager',
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
 * Renders the container-selection detail: the already-connected account, and either a container
 * selector with an explicit "Save" action plus an inline "Create new container" link (one or
 * more containers exist), or the "Create new container" link alone in place of the selector
 * (the account has zero containers — there's nothing to select). Clicking "Create new container"
 * in either state shows a local info notice reminding the merchant to refresh the page once
 * they've created it, directly above wherever that link renders.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 * @fires gla_google_tag_manager_container_connected
 * @fires gla_google_tag_manager_injection_failure
 *
 * @return {JSX.Element} The detail, or a loading spinner until the containers list has resolved.
 */
export default function ContainerSelection() {
	const { fetchGoogleTagManagerAccount } = useAppDispatch();
	const { account } = useGoogleTagManagerAccount();
	const { containers, hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();
	const [ containerId, setContainerId ] = useState();
	const [ hasClickedCreateContainer, setHasClickedCreateContainer ] =
		useState( false );
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

	const handleCreateContainerClick = () => {
		setHasClickedCreateContainer( true );
	};

	const createContainerNotice = hasClickedCreateContainer ? (
		<div className="gla-google-tag-manager-account-card__refresh-notice">
			<NoticeDetail
				status="info"
				body={
					<p>
						{ __(
							'Refresh the page to see your new container',
							'google-listings-and-ads'
						) }
					</p>
				}
			/>
		</div>
	) : null;

	/**
	 * Selects the picked container and refreshes connection state.
	 * The error is shown inline only, not as a toast — the selector and Save button stay usable,
	 * so the notice needs to stay put until the next attempt rather than flash and disappear.
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
			const { account: connectedAccount } =
				( await fetchGoogleTagManagerAccount() ) ?? {};
			recordConnectionEvents( connectedAccount );
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
						{ createContainerNotice }
						{ saveError && (
							<NoticeDetail
								status="error"
								body={
									<p>
										{ resolveErrorMessage(
											saveError,
											undefined,
											SAVE_ERROR_MESSAGE
										) }
									</p>
								}
							/>
						) }
						<Flex justify="start" gap={ 4 }>
							<AppButton
								eventName="gla_google_tag_manager_container_select_button_click"
								eventProps={ {
									context: 'settings-tag-manager',
								} }
								onClick={ handleSaveClick }
								disabled={ ! containerId || isSaving }
								loading={ isSaving }
								isPrimary
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>
							<CreateNewContainerLink
								onClick={ handleCreateContainerClick }
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
						{ createContainerNotice }
						<CreateNewContainerLink
							onClick={ handleCreateContainerClick }
						/>
					</>
				) }
			</FlexItem>
		</Flex>
	);
}
