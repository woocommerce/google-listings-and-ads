/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import noop from 'lodash/noop';
import { onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTableCard from '~/components/app-table-card';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import useUrlQuery from '~/hooks/useUrlQuery';
import useTopSearchQueries from '../hooks/useTopSearchQueries';
import ViewAllInSearchConsoleLink from './view-all-in-search-console-link';
import './top-search-queries.scss';

const ROWS_PER_PAGE = 10;
const TRACK_EVENT_REPORT_ID = 'reports-search-queries';

// The list is always ranked by clicks, so a sort request, which `TableCard` also makes when the
// ranking column is hidden, must not write to the URL the other Reports tables share.
const handleQueryChange = ( param ) =>
	param === 'sort' ? noop : onQueryChange( param );

const ERROR_MESSAGE = __(
	'Unable to load the top search queries. Please try again later.',
	'google-listings-and-ads'
);

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
		// The list is always ranked by clicks; marking the column as the default sort only
		// highlights it, as it isn't sortable.
		defaultSort: true,
		defaultOrder: 'desc',
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
 * Table of the top search queries bringing people to the store, ranked by clicks.
 *
 * Reads the date range from the URL, like every other Reports table.
 */
const TopSearchQueries = () => {
	const query = useUrlQuery();
	const { formatNumber } = useStoreCurrency();
	const { hasFinishedResolution, hasError, property, queries, limit, days } =
		useTopSearchQueries();

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

	// `TableCard` only renders a footer note through `summary`; the cap goes in as an item label,
	// which keeps the regular text weight.
	const summary = limit
		? [
				{
					value: '',
					label: sprintf(
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
			trackEventReportId={ TRACK_EVENT_REPORT_ID }
			title={ __( 'Top queries', 'google-listings-and-ads' ) }
			actions={
				<ViewAllInSearchConsoleLink
					property={ property }
					days={ days }
				/>
			}
			headers={ HEADERS }
			rows={ rows }
			totalRows={ queries.length }
			rowsPerPage={ ROWS_PER_PAGE }
			query={ {
				...query,
				orderby: 'clicks',
				order: 'desc',
				paged: page,
			} }
			onQueryChange={ handleQueryChange }
			summary={ summary }
			emptyMessage={ hasError ? ERROR_MESSAGE : undefined }
			isLoading={ ! hasFinishedResolution }
		/>
	);
};

export default TopSearchQueries;
