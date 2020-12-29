/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CheckboxControl, Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { getNewPath, getQuery, onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTableCard from '../components/app-table-card';

/**
 * All programs table, with compare feature.
 * @see AllProgramsTableCard
 */
const CompareProgramsTableCard = () => {
    const [ selectedRows, setSelectedRows ] = useState( new Set() );
	const query = getQuery();

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
            totalSpend: "$300",
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
            totalSpend: "$200",
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
            totalSpend: "$100",
            compare: false,
		},
		{
			id: 147,
			title: 'Google Free Listings',
            conversions: 89,
            clicks: 5626,
            impressions: 23340,
            itemsSold: 120,
            netSales: "$96.73",
            totalSpend: "$0",
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
			trackEventReportId="all-programs"
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
                    label: __( 'Title', 'google-listings-and-ads' ),
                    isLeftAligned: true,
                    required: true,
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
                    key: 'itemsSold',
                    label: __( 'Items Sold', 'google-listings-and-ads' ),
                    isSortable: true,
                },
                {
                    key: 'netSales',
                    label: __( 'Net Sales', 'google-listings-and-ads' ),
                    isSortable: true,
                },
                {
                    key: 'totalSpend',
                    label: __( 'Spend', 'google-listings-and-ads' ),
                    isSortable: true,
                },
            ] }
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
					{ display: row.conversions },
					{ display: row.clicks },
					{ display: row.impressions },
					{
						display: (
                            <Link href={ getNewPath( null, `/google/analytics/items-sold/${ row.id }` ) }>
                                { row.itemsSold }
                            </Link>
						),
                    },
					{ display: row.netSales },
					{ display: row.totalSpend },
				];
			} ) }
			totalRows={ data.length }
			rowsPerPage={ 10 }
			query={ query }
			onQueryChange={ onQueryChange }
		/>
	);
};

export default CompareProgramsTableCard;
