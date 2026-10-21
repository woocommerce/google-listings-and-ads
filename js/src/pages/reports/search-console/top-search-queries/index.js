/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppTableCard from '~/components/app-table-card';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import useUrlQuery from '~/hooks/useUrlQuery';
import createPagedQueryChangeHandler from '../createPagedQueryChangeHandler';
import useTopSearchQueries from '../hooks/useTopSearchQueries';
import ViewAllInSearchConsoleLink from './view-all-in-search-console-link';
import '../search-console-table.scss';

const ROWS_PER_PAGE = 10;
const TRACK_EVENT_REPORT_ID = 'reports-search-queries';

const PAGED_PARAM = 'queries_paged';

const handleQueryChange = createPagedQueryChangeHandler( PAGED_PARAM );

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
	const { hasFinishedResolution, property, queries, limit, days } =
		useTopSearchQueries();

	const lastPage = Math.ceil( queries.length / ROWS_PER_PAGE ) || 1;
	const page = Math.min(
		parseInt( query[ PAGED_PARAM ], 10 ) || 1,
		lastPage
	);

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
			className="gla-search-console-table"
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
			isLoading={ ! hasFinishedResolution }
		/>
	);
};

export default TopSearchQueries;
