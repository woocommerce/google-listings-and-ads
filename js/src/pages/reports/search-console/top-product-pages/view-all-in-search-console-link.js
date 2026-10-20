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
import { glaData } from '~/constants';
import { getSearchConsolePerformanceReportUrl } from '~/utils/urls';
import '../top-search-queries/view-all-in-search-console-link.scss';

/**
 * Clicking on the link to view all product pages in Google Search Console.
 *
 * @event gla_google_search_console_view_all_product_pages_link_click
 * @property {string} context Indicates from which page the link was clicked. Possible value: 'reports-search-console'.
 */

/**
 * Link to the pages report of a property in Google Search Console, filtered to product URLs and
 * scoped to the number of days the Reports date range covers.
 *
 * @fires gla_google_search_console_view_all_product_pages_link_click
 *
 * @param {Object} props Component props.
 * @param {string|null} props.property The connected property's raw Sites API identifier.
 * @param {number} props.days Number of days the report covers.
 * @return {JSX.Element|null} The link, or `null` when there is no property to link to.
 */
const ViewAllInSearchConsoleLink = ( { property, days } ) => {
	const url = useSearchConsoleAccountAwareUrl( property, ( siteUrl ) =>
		getSearchConsolePerformanceReportUrl( siteUrl, {
			breakdown: 'page',
			days,
			pageContains: glaData.productUrlBase,
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
			eventName="gla_google_search_console_view_all_product_pages_link_click"
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
