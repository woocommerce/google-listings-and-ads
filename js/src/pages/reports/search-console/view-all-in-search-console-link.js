/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import TrackableLink from '~/components/trackable-link';
import useSearchConsoleAccountAwareUrl from '~/hooks/useSearchConsoleAccountAwareUrl';
import { getSearchConsolePerformanceReportUrl } from '~/utils/urls';
import './view-all-in-search-console-link.scss';

/**
 * Clicking on the link to view all search queries in Google Search Console.
 *
 * @event gla_google_search_console_view_all_queries_link_click
 * @property {string} context Indicates from which page the link was clicked. Possible value: 'reports-search-console'.
 */

/**
 * Clicking on the link to view all product pages in Google Search Console.
 *
 * @event gla_google_search_console_view_all_product_pages_link_click
 * @property {string} context Indicates from which page the link was clicked. Possible value: 'reports-search-console'.
 */

/**
 * Link to a report of a property in Google Search Console, scoped to the number of days the
 * Reports date range covers.
 *
 * @fires gla_google_search_console_view_all_queries_link_click
 * @fires gla_google_search_console_view_all_product_pages_link_click
 *
 * @param {Object} props Component props.
 * @param {string|null} props.property The connected property's raw Sites API identifier.
 * @param {number} props.days Number of days the report covers.
 * @param {string} props.breakdown Dimension to break the report down by. Possible values: 'query', 'page'.
 * @param {string} props.eventName Event to record when the link is clicked.
 * @param {string} [props.pageContains] Only include pages whose URL contains this text.
 * @return {JSX.Element|null} The link, or `null` when there is no property to link to.
 */
const ViewAllInSearchConsoleLink = ( {
	property,
	days,
	breakdown,
	eventName,
	pageContains,
} ) => {
	const url = useSearchConsoleAccountAwareUrl( property, ( siteUrl ) =>
		getSearchConsolePerformanceReportUrl( siteUrl, {
			breakdown,
			days,
			pageContains,
		} )
	);

	if ( ! url ) {
		return null;
	}

	return (
		<TrackableLink
			href={ url }
			type="external"
			target="_blank"
			rel="noreferrer noopener"
			eventName={ eventName }
			eventProps={ { context: 'reports-search-console' } }
		>
			{ __( 'View all in Search Console', 'google-listings-and-ads' ) }
			<Icon
				className="gla-view-all-in-search-console-link__icon"
				icon={ external }
				size={ 16 }
			/>
		</TrackableLink>
	);
};

export default ViewAllInSearchConsoleLink;
