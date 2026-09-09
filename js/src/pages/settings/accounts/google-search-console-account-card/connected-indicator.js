/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, MenuItem } from '@wordpress/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getReportsUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';
import ConnectedBadge from '../connected-badge';
import AccountCardActions from '../account-card-actions';
import { SEARCH_CONSOLE_EVENT_CONTEXT } from './constants';

// The Reports page has no dedicated "Organic search" sub-view yet, so this links to the general
// Reports page for now — swap in a deep link once that sub-view exists.
const REPORTS_URL = getReportsUrl();

/**
 * Renders the connected indicator for the Google Search Console account card, including the connected
 * badge and the account actions menu with its "View Organic Search report" action and its
 * "Disconnect" action.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element} The connected indicator for the Google Search Console account card.
 */
const ConnectedIndicator = ( { onDisconnect } ) => {
	const handleViewReportClick = () => {
		recordGlaEvent(
			'gla_google_search_console_view_report_menu_item_click',
			{
				context: SEARCH_CONSOLE_EVENT_CONTEXT,
			}
		);
		getHistory().push( REPORTS_URL );
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
					<MenuItem onClick={ handleViewReportClick }>
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
