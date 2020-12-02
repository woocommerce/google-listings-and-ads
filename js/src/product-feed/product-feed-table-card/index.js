/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CheckboxControl, Button, Tooltip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import StyledTableCard from '../styled-table-card';
import EditProductLink from '../edit-product-link';

// TODO: data should be coming from backend API.
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

const ProductFeedTableCard = () => {
	const [ selectedRows, setSelectedRows ] = useState( new Set() );

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

	return (
		<div className="gla-product-feed-table-card">
			<StyledTableCard
				title={
					<>
						{ __( 'Product Feed', 'google-listings-and-ads' ) }
						<Tooltip
							text={ __(
								'Select one or more products',
								'google-listings-and-ads'
							) }
						>
							<Button
								isSecondary
								disabled={ selectedRows.size === 0 }
								onClick={ handleEditVisibilityClick }
							>
								{ __(
									'Edit channel visibility',
									'google-listings-and-ads'
								) }
							</Button>
						</Tooltip>
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
					},
					{
						key: 'channelVisibility',
						label: __(
							'Channel Visibility',
							'google-listings-and-ads'
						),
						isLeftAligned: true,
					},
					{
						key: 'status',
						label: __( 'Status', 'google-listings-and-ads' ),
						isLeftAligned: true,
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
			/>
		</div>
	);
};

export default ProductFeedTableCard;
