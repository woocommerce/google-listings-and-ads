/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem, ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCardTextDetail from '../../../account-card-text-detail';
import AppButton from '~/components/app-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import useGoogleTagManagerContainers from '../../hooks/useGoogleTagManagerContainers';
import useConnectGoogleTagManagerContainer from '../../hooks/useConnectGoogleTagManagerContainer';
import GoogleTagManagerContainerSelectControl from '../google-tag-manager-container-select-control';

/**
 * Clicking on the button to save the selected Google Tag Manager container.
 *
 * @event gla_google_tag_manager_container_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the container-selection step's detail: the already-selected account (looked up from
 * `getExistingGoogleTagManagerAccounts` by the connection's own `id`, since the connection record
 * itself is a flat identity+status record with no display fields), a container selector, and an
 * explicit "Save" action. The selector always renders through `AppSelectControl` (via
 * `GoogleTagManagerContainerSelectControl`), matching `AdsAccountSelectControl`'s pattern — with
 * only one candidate, its own `autoSelectFirstOption`/non-interactive handling auto-picks it and
 * renders it read-only. "Create new container" is a placeholder for the off-site
 * container-creation CTA — not yet wired up, tracked by a sibling feature.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 *
 * @return {JSX.Element|null} The detail, or `null` until the account and containers lists have resolved.
 */
export default function ContainerSelection() {
	const { account: connection } = useGoogleTagManagerAccount();
	const { existingAccounts, hasFinishedResolution: hasResolvedAccounts } =
		useExistingGoogleTagManagerAccounts();
	const { hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();
	const { selectContainer, loading } = useConnectGoogleTagManagerContainer();
	const [ containerId, setContainerId ] = useState();
	const account = existingAccounts?.find(
		( acc ) => acc.id === connection.id
	);

	if ( ! hasResolvedAccounts || ! hasResolvedContainers || ! account ) {
		return null;
	}

	const handleSaveClick = () => selectContainer( containerId );

	return (
		<Flex direction="column" gap={ 3 } expanded={ false }>
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
								<ExternalLink href={ account.tagManagerUrl } />
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
				<Flex gap={ 3 } expanded={ false } justify="start">
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
