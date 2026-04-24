/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useMarkets from '~/hooks/useMarkets';
import EditMarketModal from '../edit-market-modal';

const FIELDS = [
	{
		id: 'market',
		label: __( 'Market', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
	{
		id: 'country',
		label: __( 'Country', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
	{
		id: 'shipping',
		label: __( 'Shipping', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
];

const DEFAULT_VIEW = {
	type: 'table',
	fields: FIELDS.map( ( field ) => field.id ),
	page: 1,
	perPage: 10,
};

/**
 * Markets data table.
 *
 * Renders a basic three-column table (Market, Country, Shipping) with an Edit
 * action per row. Custom DataViews variants per shipping rate / multilingual
 * store will be added in a follow-up task; the parent passes `shippingRate`
 * today, but this placeholder ignores it.
 */
const MarketDataViews = () => {
	const { DataViews } = window.wp.dataviews;
	const { data: markets, hasFinishedResolution } = useMarkets();
	const [ view, setView ] = useState( DEFAULT_VIEW );
	const [ editingMarket, setEditingMarket ] = useState( null );

	const handleChangeView = useCallback( ( nextView ) => {
		setView( nextView );
	}, [] );

	const handleCloseEditModal = useCallback( () => {
		setEditingMarket( null );
	}, [] );

	const ACTIONS = [
		{
			id: 'edit',
			label: __( 'Edit', 'google-listings-and-ads' ),
			isPrimary: true,
			callback: ( [ market ] ) => setEditingMarket( market ),
		},
	];

	return (
		<>
			<DataViews
				getItemId={ ( item ) => item.id }
				fields={ FIELDS }
				actions={ ACTIONS }
				data={ markets }
				view={ view }
				onChangeView={ handleChangeView }
				paginationInfo={ {
					totalItems: markets.length,
					totalPages: 1,
				} }
				defaultLayouts={ { table: {} } }
				isLoading={ ! hasFinishedResolution }
			/>
			{ editingMarket && (
				<EditMarketModal
					market={ editingMarket }
					onRequestClose={ handleCloseEditModal }
				/>
			) }
		</>
	);
};

export default MarketDataViews;
