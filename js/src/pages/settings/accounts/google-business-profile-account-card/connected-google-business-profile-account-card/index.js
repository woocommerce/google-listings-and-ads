/**
 * Internal dependencies
 */
import useGoogleBusinessProfileAccount from '~/hooks/useGoogleBusinessProfileAccount';
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountCardTextDetail from '../../account-card-text-detail';
import { GOOGLE_BUSINESS_PROFILE_DESCRIPTION } from '../constants';
import formatLocationAddress from '../utils/format-location-address';
import LocationAddressLink from '../location-address-link';
import ConnectedIndicator from './connected-indicator';

/**
 * Renders the connected Google Business Profile account card: a "Connected" badge, an actions menu
 * offering "View Google Business Profile", and the connected location's address, linking out to its
 * listing on Google. Once connected, the connection record itself carries this display data, so no
 * other resolver is consulted here.
 *
 * Only rendered once the connection has resolved to `connected`, so `account` is never null here.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Business Profile account.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleBusinessProfileAccountCard = ( { onDisconnect } ) => {
	const { account } = useGoogleBusinessProfileAccount();
	const mapsUrl = useGoogleAccountAwareUrl( account.mapsUri );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_BUSINESS_PROFILE }
			description={ GOOGLE_BUSINESS_PROFILE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				<AccountCardTextDetail>
					<LocationAddressLink
						address={ formatLocationAddress( account ) }
						url={ mapsUrl }
					/>
				</AccountCardTextDetail>
			}
			indicator={
				<ConnectedIndicator
					url={ mapsUrl }
					onDisconnect={ onDisconnect }
				/>
			}
			expandedDetail
		/>
	);
};

export default ConnectedGoogleBusinessProfileAccountCard;
