/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountCardTextDetail from '../account-card-text-detail';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from './constants';
import ConnectedIndicator from './connected-indicator';

/**
 * @typedef { import('~/data/types.js').GoogleTagManagerAccount } GoogleTagManagerAccount
 */

/**
 * Renders the connected Google Tag Manager account card: a "Connected" badge, an actions menu
 * offering "Open Google Tag Manager", and the connected account/container detail text. The
 * green/red tag-injection status notices shown alongside this card belong to the sibling
 * snippet-injection feature, not this card — kept deliberately minimal.
 *
 * @param {Object} props Component props.
 * @param {GoogleTagManagerAccount} props.account The connected Google Tag Manager connection record.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleTagManagerAccountCard = ( { account, onDisconnect } ) => {
	const { name, container } = account;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				<AccountCardTextDetail>
					{ `${ name } ・ ${ container.name }` }
				</AccountCardTextDetail>
			}
			indicator={
				<ConnectedIndicator
					account={ account }
					onDisconnect={ onDisconnect }
				/>
			}
			expandedDetail
		/>
	);
};

export default ConnectedGoogleTagManagerAccountCard;
