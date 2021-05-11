/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import { CheckboxControl, Button } from '@wordpress/components';
import { getIdsFromQuery, onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useUrlQuery from '.~/hooks/useUrlQuery';
import useCurrencyFormat from '.~/hooks/useCurrencyFormat';
import useCurrencyFactory from '.~/hooks/useCurrencyFactory';
import AppTableCard from '.~/components/app-table-card';

const compareBy = 'products';
const compareParam = 'filter';
const numberFormatSetting = { precision: 0 };

/**
 * All products table, with compare feature.
 *
 * @see AllProgramsTableCard
 * @see AppTableCard
 *
 * @param {Object} props React props.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {boolean} props.loaded Whether the data have been loaded.
 * @param {Array<ProductsData>} props.products Report's products data.
 * @param {Object} [props.restProps] Properties to be forwarded to AppTableCard.
 */
const CompareProductsTableCard = ( {
	metrics,
	loaded,
	products,
	...restProps
} ) => {
	const query = useUrlQuery();
	const formatNumber = useCurrencyFormat( numberFormatSetting );
	const { formatAmount } = useCurrencyFactory();

	const [ selectedRows, setSelectedRows ] = useState( () => {
		return new Set( getIdsFromQuery( query[ compareBy ] ) );
	} );

	const loading = ! loaded;
	const rowsPerPage = products.length || 5;

	const metricHeaders = useMemo( () => {
		const headers = metrics.map( ( metric ) => ( {
			...metric,
			isSortable: true,
			isNumeric: true,
		} ) );
		headers[ 0 ].defaultSort = true;
		headers[ 0 ].defaultOrder = 'desc';

		return headers;
	}, [ metrics ] );

	/**
	 * Provides headers configuration, for AppTableCard:
	 * Interactive select all checkbox for compare; product title, and available metric headers.
	 *
	 * @param {Array<ProductsData>} data Products data.
	 *
	 * @return {import('.~/components/app-table-card').Props.headers} All headers.
	 */
	const getHeaders = ( data ) => [
		{
			key: 'compare',
			label: (
				<CheckboxControl
					disabled={ loading }
					checked={
						loaded &&
						data.length &&
						selectedRows.size === data.length
					}
					onChange={ selectAll }
				/>
			),
			required: true,
		},
		{
			key: 'title',
			label: __( 'Product title', 'google-listings-and-ads' ),
			isLeftAligned: true,
			required: true,
		},
		...metricHeaders,
	];

	/**
	 * Creates an array of metric data cells for {@link getRows},
	 * for a given row.
	 *
	 * @param {ProductsData} row Row of data for products table.
	 *
	 * @return {Array<Object>} Single row for {@link module:@woocommerce/components#TableCard.Props.rows}.
	 */
	const renderMetricDataCells = ( row ) =>
		metrics.map( ( metric ) => {
			const formatFn = metric.isCurrency ? formatAmount : formatNumber;
			return {
				display: formatFn( row.subtotals[ metric.key ] ),
			};
		} );
	/**
	 * Provides a rows configuration, for AppTableCard.
	 * Maps each data row to respective cell objects ({@link module:app-table-card.Props.rows}):
	 * checkbox to compere, product title, and available metrics cells.
	 *
	 * @param {Array<ProductsData>} data Products data.
	 *
	 * @return {Array<Object>} Rows config {@link module:@woocommerce/components#TableCard.Props.rows}.
	 */
	const getRows = ( data ) =>
		data.map( ( row ) => [
			// compare
			{
				display: (
					<CheckboxControl
						checked={ selectedRows.has( row.id ) }
						onChange={ selectRow.bind( null, row.id ) }
					/>
				),
			},
			// title
			// TODO: the product name is not yet available from API and needs to be implemented later.
			{ display: `Product #${ row.id }` },
			// merics data
			...renderMetricDataCells( row ),
		] );

	const compareSelected = () => {
		const ids = Array.from( selectedRows ).join( ',' );
		onQueryChange( 'compare' )( compareBy, compareParam, ids );
	};

	/**
	 * Selects or unselects all rows (~selectedRows).
	 *
	 * @param {boolean} checked true if all should be selected.
	 */
	const selectAll = ( checked ) => {
		if ( checked ) {
			const allIds = products.map( ( el ) => el.id );
			setSelectedRows( new Set( allIds ) );
		} else {
			setSelectedRows( new Set() );
		}
	};
	/**
	 * Selects given row, updates ~selectedRows.
	 *
	 * @param {number} rowId Id of the row to be selected.
	 * @param {boolean} checked true if the row should be selected.
	 */
	const selectRow = ( rowId, checked ) => {
		if ( checked ) {
			setSelectedRows( new Set( [ ...selectedRows, rowId ] ) );
		} else {
			selectedRows.delete( rowId );
			setSelectedRows( new Set( selectedRows ) );
		}
	};

	return (
		<AppTableCard
			title={ __( 'Products', 'google-listings-and-ads' ) }
			actions={
				<Button
					isSecondary
					disabled={ loading || selectedRows.size <= 1 }
					title={ __(
						'Select one or more products to compare',
						'google-listings-and-ads'
					) }
					onClick={ compareSelected }
				>
					{ __( 'Compare', 'google-listings-and-ads' ) }
				</Button>
			}
			isLoading={ loading }
			headers={ getHeaders( products ) }
			rows={ getRows( products ) }
			totalRows={ products.length }
			rowsPerPage={ rowsPerPage }
			query={ query }
			compareBy={ compareBy }
			compareParam={ compareParam }
			onQueryChange={ onQueryChange }
			onSort={ onQueryChange( 'sort' ) }
			{ ...restProps }
		/>
	);
};

export default CompareProductsTableCard;

/**
 * @typedef {import("../index.js").Metric} Metric
 * @typedef {import("../index.js").ProductsData} ProductsData
 */
