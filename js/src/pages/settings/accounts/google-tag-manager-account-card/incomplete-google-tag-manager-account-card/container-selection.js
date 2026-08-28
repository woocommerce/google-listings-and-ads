/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem, ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCardTextDetail from '../../account-card-text-detail';
import AppButton from '~/components/app-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import useConnectGoogleTagManagerContainer from '../hooks/useConnectGoogleTagManagerContainer';
import { getGoogleTagManagerAccountUrl } from '~/utils/urls';
import GoogleTagManagerContainerSelectControl from './google-tag-manager-container-select-control';

/**
 * Clicking on the button to save the selected Google Tag Manager container.
 *
 * @event gla_google_tag_manager_container_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the container-selection detail: the already-connected account, a container selector,
 * and an explicit "Save" action. "Create new container" is a placeholder for the off-site
 * container-creation CTA — not yet wired up, tracked by a sibling feature.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 *
 * @return {JSX.Element|null} The detail, or `null` until the containers list has resolved.
 */
export default function ContainerSelection() {
	const { account } = useGoogleTagManagerAccount();
	const { hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();
	const { selectContainer, loading } = useConnectGoogleTagManagerContainer();
	const [ containerId, setContainerId ] = useState();

	if ( ! hasResolvedContainers ) {
		return null;
	}

	const handleSaveClick = () => {
		return selectContainer( containerId );
	};

	return (
		<Flex direction="column" gap={ 3 }>
			<FlexItem>
				<AccountCardTextDetail>
					{ createInterpolateElement(
						sprintf(
							/* translators: %1$s: account name, %2$s: account ID link */
							__( '%1$s %2$s', 'google-listings-and-ads' ),
							account.name,
							'<link>' + account.id + '</link>'
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
			<FlexBlock>
				<GoogleTagManagerContainerSelectControl
					label={ __( 'Container', 'google-listings-and-ads' ) }
					value={ containerId }
					onChange={ setContainerId }
				/>
			</FlexBlock>
			<FlexItem>
				<Flex gap={ 3 } justify="start">
					<AppButton
						eventName="gla_google_tag_manager_container_select_button_click"
						eventProps={ { context: 'settings-tag-manager' } }
						onClick={ handleSaveClick }
						disabled={ ! containerId || loading }
						loading={ loading }
						isPrimary
					>
						{ __( 'Save', 'google-listings-and-ads' ) }
					</AppButton>
					{ /* TODO: remove this placeholder once "Create new container" ships (sibling feature). */ }
					<AppButton isTertiary>
						{ __(
							'Create new container',
							'google-listings-and-ads'
						) }
					</AppButton>
				</Flex>
			</FlexItem>
		</Flex>
	);
}
