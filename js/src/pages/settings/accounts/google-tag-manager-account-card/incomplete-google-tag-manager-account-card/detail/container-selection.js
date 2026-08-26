/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem, ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useConnectGoogleTagManagerContainer from '../../hooks/useConnectGoogleTagManagerContainer';
import useRefreshGoogleTagManagerConnection from '../../hooks/useRefreshGoogleTagManagerConnection';
import GoogleTagManagerContainerSelectControl from '../google-tag-manager-container-select-control';
import NoticeDetail from '../notice-detail';

/**
 * Clicking on the button to save the selected Google Tag Manager container.
 *
 * @event gla_google_tag_manager_container_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Clicking on the button to re-check for a newly created Google Tag Manager container.
 *
 * @event gla_google_tag_manager_check_connection_again_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the container-selection status's detail: the already-selected account, a selector for
 * which of that account's existing containers to connect, an explicit "Save" action, and a
 * refresh action that re-fetches this account's containers without a full page reload — so a
 * container created via the sibling off-site CTA feature appears without navigating away and
 * back. Creating a new container is that sibling feature's scope — not rendered here.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 * @fires gla_google_tag_manager_check_connection_again_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function ContainerSelection() {
	const { account: gtmAccount } = useGoogleTagManagerAccount();
	const { account } = gtmAccount;
	const { selectContainer, loading } = useConnectGoogleTagManagerContainer();
	const { refresh, isResolving } = useRefreshGoogleTagManagerConnection();
	const [ containerId, setContainerId ] = useState();

	const handleSaveClick = () => selectContainer( containerId );

	return (
		<NoticeDetail
			status="warning"
			title={ __( 'Choose a container', 'google-listings-and-ads' ) }
			body=""
			extraContent={
				<Flex direction="column" gap={ 3 } expanded={ false }>
					<FlexItem>
						{ account.name }{ ' ' }
						<ExternalLink href={ account.tagManagerUrl }>
							{ account.accountId }
						</ExternalLink>
					</FlexItem>
					<FlexBlock>
						<GoogleTagManagerContainerSelectControl
							label={ __(
								'Container',
								'google-listings-and-ads'
							) }
							value={ containerId }
							onChange={ setContainerId }
						/>
					</FlexBlock>
				</Flex>
			}
			actions={ [
				<AppButton
					key="save"
					eventName="gla_google_tag_manager_container_select_button_click"
					eventProps={ { context: 'settings-tag-manager' } }
					onClick={ handleSaveClick }
					disabled={ ! containerId || loading }
					loading={ loading }
					isPrimary
				>
					{ __( 'Save', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="check-again"
					eventName="gla_google_tag_manager_check_connection_again_button_click"
					eventProps={ { context: 'settings-tag-manager' } }
					onClick={ refresh }
					disabled={ isResolving }
					loading={ isResolving }
					isTertiary
				>
					{ __( 'Check again', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}
