/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import Badge from '~/components/badge';
import AccountCardActions from '../../account-card-actions';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from '../constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import ContainerSelection from './container-selection';
import PendingContainerNotice from './pending-container-notice';

/**
 * Renders the Google Tag Manager account card for the incomplete state: an account has already
 * been connected, but its container hasn't been picked yet. Always shows an "Action needed"
 * badge — the "Save" action lives inline in the container-selection detail itself, not in the
 * indicator. The actions menu offers "Disconnect", so a merchant who picked the wrong account
 * isn't stuck without a container to choose. When a container was created but couldn't be connected,
 * a notice offering to try again replaces the container selection.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element} The account card.
 */
const IncompleteGoogleTagManagerAccountCard = ( { onDisconnect } ) => {
	const { account } = useGoogleTagManagerAccount();

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				<Flex>
					<FlexItem>
						<Badge intent="warning">
							{ __( 'Action needed', 'google-listings-and-ads' ) }
						</Badge>
					</FlexItem>
					<FlexItem>
						<AccountCardActions
							accountTitle={ __(
								'Google Tag Manager',
								'google-listings-and-ads'
							) }
							onDisconnect={ onDisconnect }
						/>
					</FlexItem>
				</Flex>
			}
			detail={
				account?.pendingContainerId ? (
					<PendingContainerNotice />
				) : (
					<ContainerSelection />
				)
			}
			expandedDetail
		/>
	);
};

export default IncompleteGoogleTagManagerAccountCard;
