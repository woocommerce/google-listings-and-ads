/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CheckboxControl, Button } from '@wordpress/components';
import { getQuery, onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTableCard from '.~/components/app-table-card';
import { mockedListingsData, availableMetrics } from './mocked-programs-data'; // Mocked API calls

/**
 * All posible metric headers.
 * Sorted in the order we wish to render them.
 *
 * @type {import('.~/components/app-table-card').Props.headers}
 */
const metricsHeaders = [
	{
		key: 'netSales',
		label: __( 'Net Sales', 'google-listings-and-ads' ),
		isSortable: true,
	},
	{
		key: 'itemsSold',
		label: __( 'Items Sold', 'google-listings-and-ads' ),
		isSortable: true,
	},
	{
		key: 'conversions',
		label: __( 'Conversions', 'google-listings-and-ads' ),
		isSortable: true,
	},
	{
		key: 'clicks',
		label: __( 'Clicks', 'google-listings-and-ads' ),
		isSortable: true,
	},
	{
		key: 'impressions',
		label: __( 'Impressions', 'google-listings-and-ads' ),
		isSortable: true,
	},
	{
		key: 'spend',
		label: __( 'Spend', 'google-listings-and-ads' ),
		isSortable: true,
	},
];

/**
 * All programs table, with compare feature.
 *
 * @see AllProgramsTableCard
 * @see AppTableCard
 *
 * @param {Object} [props] Properties to be forwarded to AppTableCard.
 */
const CompareProgramsTableCard = ( props ) => {
	const [ selectedRows, setSelectedRows ] = useState( new Set() );
	const query = getQuery();

	// Fetch the set of available metrics, from the API.
	const availableMetricsSet = new Set( availableMetrics() );
	// Use labels and the order of columns defined here, but remove unavailable ones.
	const availableMetricHeaders = metricsHeaders.filter( ( metric ) => {
		return availableMetricsSet.has( metric.key );
	} );

	/**
	 * Provides headers configuration, for AppTableCard:
	 * Interactive select all checkbox for compare; program title, and available metric headers.
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
			label: __( 'Program', 'google-listings-and-ads' ),
			isLeftAligned: true,
			required: true,
			isSortable: true,
		},
		...availableMetricHeaders,
	];

	const unavailable = __( 'Unavailable', 'google-listings-and-ads' );
	/**
	 * Creates an array of metric data cells for {@link getRows},
	 * for a given row.
	 * Creates a cell for every ~availableMetricHeaders item, displays `"Unavailable"`, when the data is `null`.
	 *
	 * @param {Object} row Row of data for programs table.
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
	 * checkbox to compere, program title, and available metrics cells.
	 *
	 * @param {Array} data Programs data.
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

	// TODO: what happens upon clicking the "Compare" button.
	const compareSelected = () => {};

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
			className="gla-all-programs-table-card"
			title={ __( 'Programs', 'google-listings-and-ads' ) }
			actions={
				<Button
					isSecondary
					disabled={ selectedRows.size === 0 }
					title={ __(
						'Select one or more products to compare',
						'google-listings-and-ads'
					) }
					onClick={ compareSelected }
				>
					{ __( 'Compare', 'google-listings-and-ads' ) }
				</Button>
			}
			headers={ getHeaders( data ) }
			rows={ getRows( data ) }
			totalRows={ data.length }
			rowsPerPage={ 10 }
			query={ query }
			onQueryChange={ onQueryChange }
			{ ...props }
		/>
	);
};

export default CompareProgramsTableCard;
