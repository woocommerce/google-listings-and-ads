/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTableCard from '~/components/app-table-card';
import TrackableLink from '~/components/trackable-link';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import useUrlQuery from '~/hooks/useUrlQuery';
import { getSearchConsolePerformanceReportUrl } from '~/utils/urls';
import useSearchConsoleAccountAwareUrl from '~/hooks/useSearchConsoleAccountAwareUrl';
import useTopSearchQueries from '../hooks/useTopSearchQueries';
import './index.scss';

const ROWS_PER_PAGE = 10;

const HEADERS = [
	{
		key: 'query',
		label: __( 'Query', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'clicks',
		label: __( 'Clicks', 'google-listings-and-ads' ),
		isNumeric: true,
	},
	{
		key: 'impressions',
		label: __( 'Impressions', 'google-listings-and-ads' ),
		isNumeric: true,
	},
	{
		key: 'ctr',
		label: __( 'CTR', 'google-listings-and-ads' ),
		isNumeric: true,
	},
	{
		key: 'position',
		label: __( 'Average position', 'google-listings-and-ads' ),
		isNumeric: true,
	},
];

/**
 * Clicking on the link to view all search queries in Google Search Console.
 *
 * @event gla_google_search_console_view_all_queries_link_click
 * @property {string} context Indicates from which page the link was clicked. Possible value: 'reports-search-console'.
 */

/**
 * Table of the top search queries bringing people to the store, ranked by clicks.
 *
 * Reads the date range from the URL, like every other Reports table.
 *
 * @fires gla_google_search_console_view_all_queries_link_click
 */
const TopSearchQueries = () => {
	const query = useUrlQuery();
	const { formatNumber } = useStoreCurrency();
	const { loaded, property, queries, limit, days } = useTopSearchQueries();

	// A date range change can leave `paged` beyond the last page of the new results.
	const lastPage = Math.ceil( queries.length / ROWS_PER_PAGE ) || 1;
	const page = Math.min( parseInt( query.paged, 10 ) || 1, lastPage );

	const rows = useMemo( () => {
		const start = ( page - 1 ) * ROWS_PER_PAGE;

		return queries
			.slice( start, start + ROWS_PER_PAGE )
			.map( ( row ) => [
				{ display: row.query },
				{ display: formatNumber( row.clicks, 0 ) },
				{ display: formatNumber( row.impressions, 0 ) },
				{ display: `${ formatNumber( row.ctr * 100, 1 ) }%` },
				{ display: formatNumber( row.position, 1 ) },
			] );
	}, [ queries, page, formatNumber ] );

	const viewAllUrl = useSearchConsoleAccountAwareUrl( property, ( siteUrl ) =>
		getSearchConsolePerformanceReportUrl( siteUrl, {
			breakdown: 'query',
			days,
		} )
	);

	// `TableCard` only renders a footer line through `summary`, so the cap label goes in as its value.
	const summary = limit
		? [
				{
					label: '',
					value: sprintf(
						// translators: %d: maximum number of search queries listed.
						__(
							'Showing top %d queries by clicks',
							'google-listings-and-ads'
						),
						limit
					),
				},
		  ]
		: undefined;

	return (
		<AppTableCard
			className="gla-top-search-queries"
			title={ __( 'Top search queries', 'google-listings-and-ads' ) }
			actions={
				viewAllUrl && (
					<TrackableLink
						href={ viewAllUrl }
						type="external"
						eventName="gla_google_search_console_view_all_queries_link_click"
						eventProps={ { context: 'reports-search-console' } }
					>
						{ __(
							'View all in Search Console',
							'google-listings-and-ads'
						) }
					</TrackableLink>
				)
			}
			headers={ HEADERS }
			rows={ rows }
			totalRows={ queries.length }
			rowsPerPage={ ROWS_PER_PAGE }
			query={ { ...query, paged: page } }
			onQueryChange={ onQueryChange }
			summary={ summary }
			isLoading={ ! loaded }
		/>
	);
};

export default TopSearchQueries;
