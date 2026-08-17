/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink, Flex, MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import { geReportsUrl } from '~/utils/urls';
import AccountCardTextDetail from '../account-card-text-detail';
import ConnectedBadge from '../connected-badge';
import AccountCardActions from '../account-card-actions';

/**
 * @typedef { import('~/data/types.js').SearchConsoleAccount } SearchConsoleAccount
 */

/**
 * Renders the connected Search Console account card: the connected property link, a "Connected"
 * badge, and an actions menu offering "View Organic Search report".
 *
 * The Reports page has no dedicated "Organic search" sub-view yet, so this links to the general
 * Reports page for now — swap in a deep link once that sub-view exists. Disconnect isn't wired
 * up here yet — that's sibling ticket GOOWOO-916's job, which attaches its own menu item once
 * it lands.
 *
 * @param {Object} props Component props.
 * @param {SearchConsoleAccount} props.searchConsoleAccount The connected Search Console account.
 * @return {JSX.Element} The account card.
 */
export default function ConnectedSearchConsoleAccountCard( {
	searchConsoleAccount,
} ) {
	const propertyUrl = searchConsoleAccount.property?.url;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ __(
				'See how your store performs in Google Search.',
				'google-listings-and-ads'
			) }
			alignIcon="top"
			alignIndicator="top"
			detail={
				propertyUrl ? (
					<AccountCardTextDetail>
						<ExternalLink href={ propertyUrl }>
							{ propertyUrl }
						</ExternalLink>
					</AccountCardTextDetail>
				) : null
			}
			indicator={
				<Flex align="center" gap={ 3 }>
					<ConnectedBadge />
					<AccountCardActions accountTitle="Google Search Console">
						<MenuItem href={ geReportsUrl() }>
							{ __(
								'View Organic Search report',
								'google-listings-and-ads'
							) }
						</MenuItem>
					</AccountCardActions>
				</Flex>
			}
		/>
	);
}
