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
import AppTableCard from '../components/app-table-card';

/**
 * All programs table, with compare feature.
 * @see AllProgramsTableCard
 * @see AppTableCard
 *
 * @param {Object} [props] Properties to be forwarded to AppTableCard.
 */
const CompareProgramsTableCard = ( props ) => {
    const [ selectedRows, setSelectedRows ] = useState( new Set() );
    const query = getQuery();
    // TODO: DRY ProgramsReports~performanceMetrics one API is settled.
    const availableMetrics = [
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

    const unavailable = __( 'Unavailable', 'google-listings-and-ads' );
    /**
     * Creates an array of cells for
     * {@link module:app-table-card.Props.rows} -> {@link module:@woocommerce/components#TableCard.Props.rows},
     * for a given row.
     *
     * Creates a cell for every ~availableMetrics item, displays `"Unavailable"`, when the data is `null`.
     *
     * @param {Object} row Row of data for programs table.
     *
     * @returns {Array<Object>} Single row for {@link module:@woocommerce/components#TableCard.Props.rows}.
     */
    const rowRenderer = ( row ) => availableMetrics.map( ( metric ) => {
        return { display: row[ metric.key ] === null ? unavailable : row[ metric.key ] };
    });

	// TODO: data should be coming from backend API,
	// using the above query (e.g. orderby, order and page) as parameter.
	// Also, i18n for the title and numbers formatting.
	const data = [
		{
			id: 123,
			title: 'Google Smart Shopping: Fall',
            conversions: 540,
            clicks: 4152,
            impressions: 14339,
            itemsSold: 1033,
            netSales: "$2,527.91",
            spend: "$300",
            compare: false,
		},
		{
			id: 456,
			title: 'Google Smart Shopping: Core',
            conversions: 357,
            clicks: 1374,
            impressions: 43359,
            itemsSold: 456,
            netSales: "$6,204.16",
            spend: "$200",
            compare: false,
		},
		{
			id: 789,
			title: 'Google Smart Shopping: Black Friday',
            conversions: 426,
            clicks: 3536,
            impressions: 92771,
            itemsSold: 877,
            netSales: "$2,091.05",
            spend: "$100",
            compare: false,
		},
		{
			id: 147,
			title: 'Google Free Listings',
            conversions: null,
            clicks: 5626,
            impressions: 23340,
            itemsSold: null,
            netSales: null,
            spend: "$0",
            compare: false,
		},
    ];

	// TODO: what happens upon clicking the "Compare" button.
	const compareSelected = () => {};

    /**
     * Selects or unselects all rows (~selectedRows).
     *
     * @param {Boolean} checked true if the all should be selected.
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
     * @param {Boolean} checked true if the all should be selected.
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
			title={
				<>
					{ __( 'All Programs', 'google-listings-and-ads' ) }
                    <Button
                        isSecondary
                        isSmall
                        disabled={ selectedRows.size === 0 }
                        title={ __(
                            'Select one or more products to compare',
                            'google-listings-and-ads'
                        ) }
                        onClick={ compareSelected }
                    >
                        { __(
                            'Compare',
                            'google-listings-and-ads'
                        ) }
                    </Button>
				</>
			}
			headers={ [
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
            ].concat( availableMetrics ) }
			rows={ data.map( ( row ) => {
				return [
					{
						display: (
                            <CheckboxControl
                                checked={ selectedRows.has( row.id ) }
                                onChange={ selectRow.bind( null, row.id ) }
                            ></CheckboxControl>
						),
					},
					{ display: row.title },
				].concat( rowRenderer( row ) );
			} ) }
			totalRows={ data.length }
			rowsPerPage={ 10 }
			query={ query }
            onQueryChange={ onQueryChange }
            { ...props }
		/>
	);
};

export default CompareProgramsTableCard;
