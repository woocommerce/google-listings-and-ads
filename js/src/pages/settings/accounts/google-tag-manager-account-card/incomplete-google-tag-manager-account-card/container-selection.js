/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Flex, FlexItem, ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import AccountCardTextDetail from '../../account-card-text-detail';
import AppButton from '~/components/app-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import { getGoogleTagManagerAccountUrl } from '~/utils/urls';
import GoogleTagManagerContainerSelectControl from './google-tag-manager-container-select-control';
import CreateNewContainerLink from './create-new-container-link';

/**
 * Internal dependencies
 */
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
 * (the account has zero containers — there's nothing to select).
 *
 * @fires gla_google_tag_manager_container_select_button_click
 *
 * @return {JSX.Element|null} The detail, or `null` until the containers list has resolved.
 */
export default function ContainerSelection() {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleTagManagerAccount } = useAppDispatch();
	const { account } = useGoogleTagManagerAccount();
	const { containers, hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();
	const [ containerId, setContainerId ] = useState();
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

	/**
	 * Handles the "Save" button click: selects the picked container and refreshes connection state.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleSaveClick = async () => {
		try {
			await fetchSelectContainer();
			await fetchGoogleTagManagerAccount();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to select this Google Tag Manager container. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<Flex direction="column" gap={ 4 }>
			<FlexItem>
				<AccountCardTextDetail>
					{ createInterpolateElement(
						sprintf(
							/* translators: %1$s: account name, %2$s: account ID link */
							__( '%1$s %2$s', 'google-listings-and-ads' ),
							account.name,
							`<link>${ account.id }</link>`
						),
						{
							link: (
								<ExternalLink
									href={ getGoogleTagManagerAccountUrl(
										account.id
									) }
								/>
							),
						}
					) }
				</AccountCardTextDetail>
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
						<Flex justify="start">
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
							<CreateNewContainerLink />
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
						<CreateNewContainerLink />
					</>
				) }
			</FlexItem>
		</Flex>
	);
}
