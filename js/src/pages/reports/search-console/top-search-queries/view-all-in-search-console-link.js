/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TrackableLink from '~/components/trackable-link';
import useSearchConsoleAccountAwareUrl from '~/hooks/useSearchConsoleAccountAwareUrl';
import { getSearchConsolePerformanceReportUrl } from '~/utils/urls';

/**
 * Clicking on the link to view all search queries in Google Search Console.
 *
 * @event gla_google_search_console_view_all_queries_link_click
 * @property {string} context Indicates from which page the link was clicked. Possible value: 'reports-search-console'.
 */

/**
 * Link to the full search queries report of a property in Google Search Console, scoped to the
 * number of days the Reports date range covers.
 *
 * @fires gla_google_search_console_view_all_queries_link_click
 *
 * @param {Object} props Component props.
 * @param {string|null} props.property The connected property's raw Sites API identifier.
 * @param {number} props.days Number of days the report covers.
 * @return {JSX.Element|null} The link, or `null` when there is no property to link to.
 */
const ViewAllInSearchConsoleLink = ( { property, days } ) => {
	const url = useSearchConsoleAccountAwareUrl( property, ( siteUrl ) =>
		getSearchConsolePerformanceReportUrl( siteUrl, {
			breakdown: 'query',
			days,
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
			eventName="gla_google_search_console_view_all_queries_link_click"
			eventProps={ { context: 'reports-search-console' } }
		>
			{ __( 'View all in Search Console', 'google-listings-and-ads' ) }
		</TrackableLink>
	);
};

export default ViewAllInSearchConsoleLink;
