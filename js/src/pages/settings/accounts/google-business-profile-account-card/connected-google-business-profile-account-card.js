/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink, Flex, FlexItem, MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';
import AccountCardTextDetail from '../account-card-text-detail';
import AccountCardActions from '../account-card-actions';
import ConnectedBadge from '../connected-badge';
import { GOOGLE_BUSINESS_PROFILE_DESCRIPTION } from './constants';
import formatLocationAddress from './format-location-address';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Renders the connected Google Business Profile card: a "Connected" badge, an actions menu with
 * "View Google Business Profile" and "Disconnect", and the connected location's address. Both the
 * address and the menu item link to the location's listing on Google under the connected Google
 * Account.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileLocation} props.location The connected location.
 * @param {() => void} props.onDisconnect Called when the merchant chooses to disconnect.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleBusinessProfileAccountCard = ( {
	location,
	onDisconnect,
} ) => {
	const { mapsUri } = location;
	const accountAwareMapsUri = useGoogleAccountAwareUrl( mapsUri );
	const listingUrl = mapsUri ? accountAwareMapsUri : null;
	const address = formatLocationAddress( location );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_BUSINESS_PROFILE }
			description={ GOOGLE_BUSINESS_PROFILE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				<AccountCardTextDetail>
					{ listingUrl ? (
						<ExternalLink href={ listingUrl }>
							{ address }
						</ExternalLink>
					) : (
						address
					) }
				</AccountCardTextDetail>
			}
			indicator={
				<Flex>
					<FlexItem>
						<ConnectedBadge />
					</FlexItem>
					<FlexItem>
						<AccountCardActions
							accountTitle={ __(
								'Google Business Profile',
								'google-listings-and-ads'
							) }
							onDisconnect={ onDisconnect }
						>
							{ listingUrl && (
								<MenuItem
									href={ listingUrl }
									target="_blank"
									rel="noreferrer"
								>
									{ __(
										'View Google Business Profile',
										'google-listings-and-ads'
									) }
								</MenuItem>
							) }
						</AccountCardActions>
					</FlexItem>
				</Flex>
			}
			expandedDetail
		/>
	);
};

export default ConnectedGoogleBusinessProfileAccountCard;
