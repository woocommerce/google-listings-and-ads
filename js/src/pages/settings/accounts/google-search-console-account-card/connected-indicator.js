/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { queueRecordGlaEvent } from '~/utils/tracks';
import { getReportsUrl } from '~/utils/urls';
import ConnectedBadge from '../connected-badge';
import AccountCardActions from '../account-card-actions';
import { SEARCH_CONSOLE_EVENT_CONTEXT } from './constants';

/**
 * Renders the connected indicator for the Google Search Console account card, including the connected
 * badge and the account actions menu with its "View Organic Search report" action (which opens
 * the Organic search tab of this plugin's Reports page) and its "Disconnect" action.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element} The connected indicator for the Google Search Console account card.
 */
const ConnectedIndicator = ( { onDisconnect } ) => {
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
					<MenuItem
						href={ getReportsUrl( {
							reportKey: 'search-console',
						} ) }
						onClick={ handleViewReportClick }
					>
						{ __(
							'View Organic Search report',
							'google-listings-and-ads'
						) }
					</MenuItem>
				</AccountCardActions>
			</FlexItem>
		</Flex>
	);
};

export default ConnectedIndicator;
