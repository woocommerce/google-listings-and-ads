/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppTableCard from '~/components/app-table-card';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import useUrlQuery from '~/hooks/useUrlQuery';
import createPagedQueryChangeHandler from './createPagedQueryChangeHandler';
import './search-console-table.scss';

const ROWS_PER_PAGE = 10;

/**
 * Table of Search Console rows ranked by clicks: a first column naming the row, then clicks,
 * impressions, CTR and average position.
 *
 * Reads the date range from the URL, like every other Reports table. Each table keeps its page
 * in its own URL param.
 *
 * @param {Object} props Component props.
 * @param {string} [props.className] Class name of the table card, besides the shared one.
 * @param {string} props.trackEventReportId Report ID sent with the table's tracking events.
 * @param {string} props.title Title of the table card.
 * @param {JSX.Element} props.actions Actions in the table card header.
 * @param {{ key: string, label: string }} props.nameColumn Key and label of the first column. Keep it a stable reference, such as a module-level constant, since the table headers are memoised on it.
 * @param {Array<Object>} props.items Rows of the report, ranked by clicks.
 * @param {(item: Object) => string} props.getName Gets the text of an item's first column.
 * @param {string} props.pagedParam URL param holding the table's page number.
 * @param {number|null} props.limit Maximum number of rows the report returns, `null` while loading.
 * @param {Function} props.getSummaryLabel Gets the footer label from `{ count, limit }`, the number of rows and the limit, or `null` for none.
 * @param {boolean} props.hasFinishedResolution Whether the report has finished loading.
 */
const SearchConsoleTopTable = ( {
	className,
	trackEventReportId,
	title,
	actions,
	nameColumn,
	items,
	getName,
	pagedParam,
	limit,
	getSummaryLabel,
	hasFinishedResolution,
} ) => {
	const query = useUrlQuery();
	const { formatNumber } = useStoreCurrency();

	const headers = useMemo(
		() => [
			{
				...nameColumn,
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
		],
		[ nameColumn ]
	);

	const handleQueryChange = useMemo(
		() => createPagedQueryChangeHandler( pagedParam ),
		[ pagedParam ]
	);

	const lastPage = Math.ceil( items.length / ROWS_PER_PAGE ) || 1;
	const page = Math.min( parseInt( query[ pagedParam ], 10 ) || 1, lastPage );

	const rows = useMemo( () => {
		const start = ( page - 1 ) * ROWS_PER_PAGE;

		return items
			.slice( start, start + ROWS_PER_PAGE )
			.map( ( row ) => [
				{ display: getName( row ) },
				{ display: formatNumber( row.clicks, 0 ) },
				{ display: formatNumber( row.impressions, 0 ) },
				{ display: `${ formatNumber( row.ctr * 100, 1 ) }%` },
				{ display: formatNumber( row.position, 1 ) },
			] );
	}, [ items, page, getName, formatNumber ] );

	const summaryLabel =
		limit === null
			? null
			: getSummaryLabel( { count: items.length, limit } );

	return (
		<AppTableCard
			className={ classnames( 'gla-search-console-table', className ) }
			trackEventReportId={ trackEventReportId }
			title={ title }
			actions={ actions }
			headers={ headers }
			rows={ rows }
			totalRows={ items.length }
			rowsPerPage={ ROWS_PER_PAGE }
			query={ {
				...query,
				orderby: 'clicks',
				order: 'desc',
				paged: page,
			} }
			onQueryChange={ handleQueryChange }
			summary={
				summaryLabel
					? [ { value: '', label: summaryLabel } ]
					: undefined
			}
			isLoading={ ! hasFinishedResolution }
		/>
	);
};

export default SearchConsoleTopTable;
