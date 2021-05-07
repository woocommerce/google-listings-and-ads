/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import { CheckboxControl, Button } from '@wordpress/components';
import {
	getQuery,
	getIdsFromQuery,
	onQueryChange,
} from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTableCard from '.~/components/app-table-card';
import { mockedListingsData } from './mocked-products-data'; // Mocked API calls

const compareBy = 'products';
const compareParam = 'filter';

/**
 * All products table, with compare feature.
 *
 * @see AllProgramsTableCard
 * @see AppTableCard
 *
 * @param {Object} props React props.
 * @param {[[string, string]]} props.metrics Metrics array of each metric tuple [key, label].
 * @param {Object} [props.restProps] Properties to be forwarded to AppTableCard.
 */
const CompareProductsTableCard = ( { metrics, ...restProps } ) => {
	const query = getQuery();
	const [ selectedRows, setSelectedRows ] = useState( () => {
		return new Set( getIdsFromQuery( query[ compareBy ] ) );
	} );

	const availableMetricHeaders = useMemo( () => {
		const headers = metrics.map( ( [ key, label ] ) => ( {
			key,
			label,
			isSortable: true,
		} ) );
		headers[ 0 ].defaultSort = true;
		headers[ 0 ].defaultOrder = 'desc';

		return headers;
	}, [ metrics ] );

	/**
	 * Provides headers configuration, for AppTableCard:
	 * Interactive select all checkbox for compare; product title, and available metric headers.
	 *
	 * @param {Array} data
	 *
	 * @return {import('.~/components/app-table-card').Props.headers} All headers.
	 */
	const getHeaders = ( data ) => [
		{
			key: 'compare',
			label: (
				<CheckboxControl
					checked={ selectedRows.size === data.length }
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
		...availableMetricHeaders,
	];

	const unavailable = __( 'Unavailable', 'google-listings-and-ads' );
	/**
	 * Creates an array of metric data cells for {@link getRows},
	 * for a given row.
	 * Creates a cell for every ~availableMetricHeaders item, displays `"Unavailable"`, when the data is `null`.
	 *
	 * @param {Object} row Row of data for products table.
	 *
	 * @return {Array<Object>} Single row for {@link module:@woocommerce/components#TableCard.Props.rows}.
	 */
	const renderMetricDataCells = ( row ) =>
		availableMetricHeaders.map( ( metric ) => {
			return {
				display:
					row[ metric.key ] === null
						? unavailable
						: row[ metric.key ],
			};
		} );
	/**
	 * Provides a rows configuration, for AppTableCard.
	 * Maps each data row to respective cell objects ({@link module:app-table-card.Props.rows}):
	 * checkbox to compere, product title, and available metrics cells.
	 *
	 * @param {Array} data Products data.
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
					></CheckboxControl>
				),
			},
			// title
			{ display: row.title },
			// merics data
			...renderMetricDataCells( row ),
		] );

	// TODO: data should be coming from backend API,
	// using the above query (e.g. orderby, order and page) as parameter.
	// Also, i18n for the title and numbers formatting.
	const data = mockedListingsData();

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
			const allIds = data.map( ( el ) => el.id );
			setSelectedRows( new Set( allIds ) );
		} else {
			setSelectedRows( new Set() );
		}
	};
	/**
	 * Selects given row, updates ~selectedRows.
	 *
	 * @param {*} rowId Id of the row to be selected.
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
			actions={ [
				<Button
					key="compare"
					isSecondary
					disabled={ selectedRows.size <= 1 }
					title={ __(
						'Select one or more products to compare',
						'google-listings-and-ads'
					) }
					onClick={ compareSelected }
				>
					{ __( 'Compare', 'google-listings-and-ads' ) }
				</Button>,
			] }
			headers={ getHeaders( data ) }
			rows={ getRows( data ) }
			totalRows={ data.length }
			rowsPerPage={ 10 }
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
