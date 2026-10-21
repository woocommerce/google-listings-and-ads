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
import useTopProductPages from '../hooks/useTopProductPages';
import ViewAllInSearchConsoleLink from './view-all-in-search-console-link';
import '../search-console-table.scss';
import './top-product-pages.scss';

const ROWS_PER_PAGE = 10;
const TRACK_EVENT_REPORT_ID = 'reports-product-pages';

const PAGED_PARAM = 'products_paged';

const handleQueryChange = createPagedQueryChangeHandler( PAGED_PARAM );

const HEADERS = [
	{
		key: 'product',
		label: __( 'Product', 'google-listings-and-ads' ),
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
 * Table of the top product pages bringing people to the store, ranked by clicks.
 *
 * Reads the date range from the URL, like every other Reports table. Callers must not render it
 * for service-based merchants, who have no products.
 */
const TopProductPages = () => {
	const query = useUrlQuery();
	const { formatNumber } = useStoreCurrency();
	const { hasFinishedResolution, property, products, limit, days } =
		useTopProductPages();

	const lastPage = Math.ceil( products.length / ROWS_PER_PAGE ) || 1;
	const page = Math.min(
		parseInt( query[ PAGED_PARAM ], 10 ) || 1,
		lastPage
	);

	const rows = useMemo( () => {
		const start = ( page - 1 ) * ROWS_PER_PAGE;

		return products
			.slice( start, start + ROWS_PER_PAGE )
			.map( ( row ) => [
				{ display: row.name },
				{ display: formatNumber( row.clicks, 0 ) },
				{ display: formatNumber( row.impressions, 0 ) },
				{ display: `${ formatNumber( row.ctr * 100, 1 ) }%` },
				{ display: formatNumber( row.position, 1 ) },
			] );
	}, [ products, page, formatNumber ] );

	const summary = limit
		? [
				{
					value: '',
					label: sprintf(
						// translators: %d: maximum number of product pages listed.
						__(
							'Showing top %d product pages by clicks',
							'google-listings-and-ads'
						),
						limit
					),
				},
		  ]
		: undefined;

	return (
		<AppTableCard
			className="gla-top-product-pages gla-search-console-table"
			trackEventReportId={ TRACK_EVENT_REPORT_ID }
			title={ __( 'Top product pages', 'google-listings-and-ads' ) }
			actions={
				<ViewAllInSearchConsoleLink
					property={ property }
					days={ days }
				/>
			}
			headers={ HEADERS }
			rows={ rows }
			totalRows={ products.length }
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

export default TopProductPages;
