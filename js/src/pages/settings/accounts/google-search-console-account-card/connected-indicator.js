/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Flex,
	FlexItem,
	MenuItem,
	VisuallyHidden,
} from '@wordpress/components';
import { external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { queueRecordGlaEvent } from '~/utils/tracks';
import { getSearchConsolePerformanceReportUrl } from '~/utils/urls';
import ConnectedBadge from '../connected-badge';
import AccountCardActions from '../account-card-actions';
import { SEARCH_CONSOLE_EVENT_CONTEXT } from './constants';

/**
 * @typedef { import('~/data/types.js').GoogleSearchConsoleAccount } GoogleSearchConsoleAccount
 */

/**
 * Renders the connected indicator for the Google Search Console account card, including the connected
 * badge and the account actions menu with its "View Organic Search report" action and its
 * "Disconnect" action.
 *
 * @param {Object} props Component props.
 * @param {GoogleSearchConsoleAccount} props.account The connected Google Search Console account.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element} The connected indicator for the Google Search Console account card.
 */
const ConnectedIndicator = ( { account, onDisconnect } ) => {
	const reportUrl = getSearchConsolePerformanceReportUrl( account.site_url );

	const handleViewReportClick = () => {
		queueRecordGlaEvent(
			'gla_google_search_console_view_report_menu_item_click',
			{
				context: SEARCH_CONSOLE_EVENT_CONTEXT,
			}
		);
	};

	return (
		<Flex>
			<FlexItem>
				<ConnectedBadge />
			</FlexItem>
			<FlexItem>
				<AccountCardActions
					accountTitle={ __(
						'Google Search Console',
						'google-listings-and-ads'
					) }
					onDisconnect={ onDisconnect }
				>
					{ reportUrl && (
						<MenuItem
							href={ reportUrl }
							target="_blank"
							rel="noreferrer noopener"
							icon={ external }
							onClick={ handleViewReportClick }
						>
							{ __(
								'View Organic Search report',
								'google-listings-and-ads'
							) }
							<VisuallyHidden as="span">
								{
									/* translators: accessibility text */
									__(
										'(opens in a new tab)',
										'google-listings-and-ads'
									)
								}
							</VisuallyHidden>
						</MenuItem>
					) }
				</AccountCardActions>
			</FlexItem>
		</Flex>
	);
};

export default ConnectedIndicator;
