/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import { CheckboxControl, Button } from '@wordpress/components';
import { onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getIdsFromQuery } from './utils';
import useUrlQuery from '.~/hooks/useUrlQuery';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import AppTableCard from '.~/components/app-table-card';

/**
 * All data table, with compare feature.
 *
 * @see AllProgramsTableCard
 * @see AppTableCard
 * @param {Object} props React props.
 * @param {string} props.compareBy Query parameter that stores ids to compare.
 * @param {string} props.compareParam Query parameter to indicate comparing mode.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {boolean} props.isLoading Whether the data is still being loaded.
 * @param {string} props.compareButonTitle Title for "Compare" button.
 * @param {Array<ReportData>} props.data Report's data to be shown.
 * @param {string} props.nameHeader Column header for the name property.
 * @param {(row: ReportData) => JSX.Element} props.nameCell Function to rendername cell.
 * @param {Object} [props.restProps] Properties to be forwarded to AppTableCard.
 *
 * @template {ReportData} ReportData
 */
const CompareTableCard = ( {
	compareBy,
	compareParam,
	metrics,
	isLoading,
	compareButonTitle,
	data,
	nameHeader,
	nameCell,
	...restProps
} ) => {
	const storeCurrencyConfig = useStoreCurrency();
	const query = useUrlQuery();

	const [ selectedRows, setSelectedRows ] = useState( () => {
		return new Set( getIdsFromQuery( query[ compareBy ] ) );
	} );

	const rowsPerPage = data.length || 5;

	const metricHeaders = useMemo( () => {
		if ( ! metrics.length ) {
			return [];
		}
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
	 * Interactive select all checkbox for compare; product/program title, and available metric headers.
	 *
	 * @param {Array<ReportData>} reportData Report data.
	 *
	 * @return {import('.~/components/app-table-card').Props.headers} All headers.
	 */
	const getHeaders = ( reportData ) => [
		{
			key: 'compare',
			label: (
				<CheckboxControl
					disabled={ isLoading }
					checked={
						! isLoading &&
						reportData.length &&
						selectedRows.size === reportData.length
					}
					onChange={ selectAll }
				/>
			),
			required: true,
		},
		{
			key: 'title',
			label: nameHeader,
			isLeftAligned: true,
			required: true,
		},
		...metricHeaders,
	];

	/**
	 * Creates an array of metric cells for {@link getRows},
	 * for a given row.
	 * Creates a cell for every ~metric item, displays `"Unavailable"`, when the data is `null`.
	 *
	 * @param {ReportData} row Row of data for data table.
	 *
	 * @return {Array<Object>} Single row for {@link module:@woocommerce/components#TableCard.Props.rows}.
	 */
	const renderMetricDataCells = ( row ) =>
		metrics.map( ( metric ) => {
			const value = row.subtotals[ metric.key ];
			return {
				display: metric.formatFn( storeCurrencyConfig, value ),
			};
		} );
	/**
	 * Provides a rows configuration, for AppTableCard.
	 * Maps each data row to respective cell objects ({@link module:app-table-card.Props.rows}):
	 * checkbox to compere, product/program title, and available metrics cells.
	 *
	 * @param {Array<ReportData>} reportData Report data.
	 *
	 * @return {Array<Object>} Rows config {@link module:@woocommerce/components#TableCard.Props.rows}.
	 */
	const getRows = ( reportData ) =>
		reportData.map( ( row ) => [
			// compare
			{
				display: (
					<CheckboxControl
						checked={ selectedRows.has( row.id ) }
						onChange={ selectRow.bind( null, row.id ) }
					/>
				),
			},
			// name/title
			{ display: nameCell( row ) },
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
			const allIds = data.map( ( el ) => el.id );
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
			actions={
				<Button
					isSecondary
					disabled={ isLoading || selectedRows.size <= 1 }
					title={ compareButonTitle }
					onClick={ compareSelected }
				>
					{ __( 'Compare', 'google-listings-and-ads' ) }
				</Button>
			}
			isLoading={ isLoading }
			headers={ getHeaders( data ) }
			rows={ getRows( data ) }
			totalRows={ data.length }
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

export default CompareTableCard;

/**
 * @typedef {import("./index.js").Metric} Metric
 * @typedef {import("./index.js").ProductsData} ProductsData
 * @typedef {import("./index.js").ProgramsData} ProgramsData
 * @typedef {ProductsData | ProgramsData} ReportData
 */
