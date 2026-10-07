/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountCardTextDetail from '../account-card-text-detail';
import { GOOGLE_BUSINESS_PROFILE_DESCRIPTION } from './constants';
import LocationNameWithLink from './location-name-with-link';
import ConnectedIndicator from './connected-indicator';

/**
 * @typedef { import('~/data/types.js').GoogleBusinessProfileConnection } GoogleBusinessProfileConnection
 */

/**
 * Renders the connected Google Business Profile account card: a "Connected" badge, an actions menu
 * offering "View Google Business Profile", and the connected location's address, linking out to its
 * listing on Google. Once connected, the connection record itself carries this display data, so no
 * other resolver is consulted here.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileConnection} props.account The connected Google Business Profile connection record.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Business Profile account.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleBusinessProfileAccountCard = ( {
	account,
	onDisconnect,
} ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_BUSINESS_PROFILE }
			description={ GOOGLE_BUSINESS_PROFILE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				<AccountCardTextDetail>
					<LocationNameWithLink location={ account } />
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

export default ConnectedGoogleBusinessProfileAccountCard;
