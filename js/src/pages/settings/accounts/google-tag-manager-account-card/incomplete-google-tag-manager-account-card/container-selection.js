/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_SETTINGS_CONTEXT } from '../constants';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { handleApiError } from '~/utils/handleError';
import AccountCardTextDetail from '../../account-card-text-detail';
import AppButton from '~/components/app-button';
import AppSpinner from '~/components/app-spinner';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import AccountNameWithLink from '../account-name-with-link';
import AdsConversionDuplicateNotice from '../ads-conversion-duplicate-notice';
import useRefreshGoogleTagManagerConnection from '../hooks/useRefreshGoogleTagManagerConnection';
import GoogleTagManagerContainerSelectControl from './google-tag-manager-container-select-control';
import CreateNewContainerButton from './create-new-container-button';
import ReadOnlyNotice from './read-only-notice';
import { CREATED_NOT_CONNECTED_MESSAGE } from './pending-container-notice';
import './container-selection.scss';

/**
 * Clicking on the button to save the selected Google Tag Manager container.
 *
 * @event gla_google_tag_manager_container_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the container-selection detail: the already-connected account, and either a container
 * selector with an explicit "Save" action plus an inline "Create new container" link (one or
 * more containers exist), or the "Create new container" link alone in place of the selector
 * (the account has zero containers — there's nothing to select). Clicking "Create new container"
 * in either state opens a modal that creates and connects a container. When the connection
 * can't create containers, an explanation is shown in place of the link.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 *
 * @return {JSX.Element} The detail, or a loading spinner until the account and the containers list have resolved.
 */
export default function ContainerSelection() {
	const refreshConnection = useRefreshGoogleTagManagerConnection();
	const { account, hasFinishedResolution: hasResolvedAccount } =
		useGoogleTagManagerAccount();
	const { containers, hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();
	const [ containerId, setContainerId ] = useState();
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

	const handleCreateComplete = async () => {
		const refreshedAccount = await refreshConnection();

		// The container is already created, so a refresh that reports neither a connected nor a pending
		// container is reported to the modal. Otherwise the card switches to its connected state or to the
		// notice for a pending container, which removes this detail and its modal.
		if (
			refreshedAccount?.status !==
				GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED &&
			! refreshedAccount?.pendingContainerId
		) {
			throw new Error( CREATED_NOT_CONNECTED_MESSAGE );
		}
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
									onCreated={ handleCreateComplete }
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
							<CreateNewContainerButton
								onCreated={ handleCreateComplete }
							/>
						</>
					) }
				</Flex>
			</FlexItem>
		</Flex>
	);
}
