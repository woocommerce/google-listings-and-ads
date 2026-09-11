/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { handleApiError, resolveErrorMessage } from '~/utils/handleError';
import AccountCardTextDetail from '../../account-card-text-detail';
import AppButton from '~/components/app-button';
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

const SAVE_ERROR_MESSAGE = __(
	'Unable to select this Google Tag Manager container. Please try again.',
	'google-listings-and-ads'
);

/**
 * Renders the container-selection detail: the already-connected account, and either a container
 * selector with an explicit "Save" action plus an inline "Create new container" link (one or
 * more containers exist), or the "Create new container" link alone in place of the selector
 * (the account has zero containers — there's nothing to select). Clicking "Create new container"
 * in either state shows a local info notice reminding the merchant to refresh the page once
 * they've created it, directly above wherever that link renders.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 *
 * @return {JSX.Element|null} The detail, or `null` until the containers list has resolved.
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
	const [ fetchSelectContainer, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/containers`,
		method: 'POST',
		data: {
			id: containerId,
		},
	} );

	if ( ! hasResolvedContainers ) {
		return null;
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
	 * Handles the "Save" button click: selects the picked container and refreshes connection state.
	 * A failure is kept visible inline (in addition to the transient toast) since, unlike the
	 * account-connect step, there's no separate "Try again" action here — the selector and Save
	 * button stay usable, so the notice needs to stay put until the next attempt rather than
	 * vanishing with nothing left in the card to explain what happened.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleSaveClick = async () => {
		try {
			await fetchSelectContainer();
			await fetchGoogleTagManagerAccount();
			setSaveError( null );
		} catch ( error ) {
			setSaveError( error );
			handleApiError( error, undefined, SAVE_ERROR_MESSAGE );
		}
	};

	const saveErrorNotice = saveError ? (
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
	) : null;

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
						{ saveErrorNotice }
						<Flex justify="start" gap={ 4 }>
							<AppButton
								eventName="gla_google_tag_manager_container_select_button_click"
								eventProps={ {
									context: 'settings-tag-manager',
								} }
								onClick={ handleSaveClick }
								disabled={ ! containerId || loading }
								loading={ loading }
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
