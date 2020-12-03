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
import StyledTableCard from '../styled-table-card';
import EditProductLink from '../edit-product-link';

const ProductFeedTableCard = () => {
	const [ selectedRows, setSelectedRows ] = useState( new Set() );
	const { orderby, order } = getQuery();

	// TODO: data should be coming from backend API,
	// using the above orderby and order as parameter.
	// Also, i18n for the display labels too.
	const data = [
		{
			id: 123,
			title: 'Pink marble tee',
			visibility: 'Sync and show',
			status: 'Not synced',
		},
		{
			id: 456,
			title: 'Brown socks',
			visibility: 'Sync and show',
			status: 'Not synced',
		},
	];

	// TODO: what happens upon clicking the Edit Visibility button.
	const handleEditVisibilityClick = () => {};

	const handleSelectAllCheckboxChange = ( checked ) => {
		if ( checked ) {
			const ids = data.map( ( el ) => el.id );
			setSelectedRows( new Set( [ ...ids ] ) );
		} else {
			setSelectedRows( new Set() );
		}
	};
	const getHandleSelectRowCheckboxChange = ( productId ) => ( checked ) => {
		if ( checked ) {
			setSelectedRows( new Set( [ ...selectedRows, productId ] ) );
		} else {
			selectedRows.delete( productId );
			setSelectedRows( new Set( selectedRows ) );
		}
	};

	const handleSort = ( key, direction ) => {
		onQueryChange( 'sort' )( key, direction );
	};

	return (
		<div className="gla-product-feed-table-card">
			<StyledTableCard
				title={
					<>
						{ __( 'Product Feed', 'google-listings-and-ads' ) }
						<Button
							isSecondary
							isSmall
							disabled={ selectedRows.size === 0 }
							title={ __(
								'Select one or more products',
								'google-listings-and-ads'
							) }
							onClick={ handleEditVisibilityClick }
						>
							{ __(
								'Edit channel visibility',
								'google-listings-and-ads'
							) }
						</Button>
					</>
				}
				headers={ [
					{
						key: 'select',
						label: (
							<CheckboxControl
								checked={ selectedRows.size === data.length }
								onChange={ handleSelectAllCheckboxChange }
							/>
						),
						isLeftAligned: true,
						required: true,
					},
					{
						key: 'productTitle',
						label: __( 'Product Title', 'google-listings-and-ads' ),
						isLeftAligned: true,
						required: true,
						isSortable: true,
						defaultSort: orderby === 'productTitle',
						defaultOrder: orderby === 'productTitle' && order,
					},
					{
						key: 'channelVisibility',
						label: __(
							'Channel Visibility',
							'google-listings-and-ads'
						),
						isLeftAligned: true,
						isSortable: true,
						defaultSort: orderby === 'channelVisibility',
						defaultOrder: orderby === 'channelVisibility' && order,
					},
					{
						key: 'status',
						label: __( 'Status', 'google-listings-and-ads' ),
						isLeftAligned: true,
						isSortable: true,
						defaultSort: orderby === 'status',
						defaultOrder: orderby === 'status' && order,
					},
					{ key: 'action', label: '', required: true },
				] }
				rows={ data.map( ( el ) => {
					return [
						{
							display: (
								<CheckboxControl
									checked={ selectedRows.has( el.id ) }
									onChange={ getHandleSelectRowCheckboxChange(
										el.id
									) }
								></CheckboxControl>
							),
						},
						{ display: el.title },
						{ display: el.visibility },
						{
							display: el.status,
						},
						{
							display: <EditProductLink productId={ el.id } />,
						},
					];
				} ) }
				totalRows={ data.length }
				rowsPerPage={ 10 }
				summary={ [
					{
						label: __( 'products', 'google-listings-and-ads' ),
						value: data.length,
					},
				] }
				onSort={ handleSort }
			/>
		</div>
	);
};

export default ProductFeedTableCard;
