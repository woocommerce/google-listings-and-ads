/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	BaseControl,
	Flex,
	FlexBlock,
	FlexItem,
	ExternalLink,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCardTextDetail from '../../../account-card-text-detail';
import AppButton from '~/components/app-button';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useConnectGoogleTagManagerContainer from '../../hooks/useConnectGoogleTagManagerContainer';
import GoogleTagManagerContainerSelectControl from '../google-tag-manager-container-select-control';

/**
 * Clicking on the button to save the selected Google Tag Manager container.
 *
 * @event gla_google_tag_manager_container_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the container-selection status's detail: the already-selected account, the single
 * candidate container shown as plain text, or a selector when more than one exists, and an
 * explicit "Save" action. "Create new container" is a placeholder for the off-site
 * container-creation CTA — not yet wired up, tracked by a sibling feature.
 *
 * @fires gla_google_tag_manager_container_select_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function ContainerSelection() {
	const { account: gtmAccount } = useGoogleTagManagerAccount();
	const { account, containers } = gtmAccount;
	const { selectContainer, loading } = useConnectGoogleTagManagerContainer();
	const [ containerId, setContainerId ] = useState();
	const [ singleContainer ] = containers;
	const hasMultipleContainers = containers.length > 1;

	// With only one candidate there's nothing to pick — auto-select it so "Save" enables
	// without showing a selector that only ever has one option.
	useEffect( () => {
		if ( ! hasMultipleContainers ) {
			setContainerId( singleContainer.containerId );
		}
	}, [ hasMultipleContainers, singleContainer ] );

	const handleSaveClick = () => selectContainer( containerId );

	return (
		<Flex direction="column" gap={ 3 } expanded={ false }>
			<FlexItem>
				<AccountCardTextDetail>
					{ account.name }{ ' ' }
					<ExternalLink href={ account.tagManagerUrl }>
						{ account.accountId }
					</ExternalLink>
				</AccountCardTextDetail>
			</FlexItem>
			<FlexBlock>
				{ hasMultipleContainers ? (
					<GoogleTagManagerContainerSelectControl
						label={ __( 'Container', 'google-listings-and-ads' ) }
						value={ containerId }
						onChange={ setContainerId }
					/>
				) : (
					<BaseControl
						id="gla-google-tag-manager-account-card__container"
						label={ __( 'Container', 'google-listings-and-ads' ) }
						__nextHasNoMarginBottom
					>
						{ `${ singleContainer.name } (${ singleContainer.publicId })` }
					</BaseControl>
				) }
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
