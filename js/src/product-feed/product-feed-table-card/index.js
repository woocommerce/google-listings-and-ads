/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CheckboxControl, Button } from '@wordpress/components';
import { getQuery, onQueryChange } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import StyledTableCard from '../../components/styled-table-card';
import EditProductLink from '../edit-product-link';

const recordTableHeaderToggleEvent = ( report, column, status ) => {
	recordEvent( 'gla_table_header_toggle', {
		report,
		column,
		status,
	} );
};

const ProductFeedTableCard = () => {
	const [ selectedRows, setSelectedRows ] = useState( new Set() );
	const query = getQuery();

	// TODO: data should be coming from backend API,
	// using the above query (e.g. orderby, order and page) as parameter.
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

	// TODO: total should be coming from API response above.
	const total = data.length;

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

	const handleColumnsChange = ( shown, toggled ) => {
		const status = shown.includes( toggled ) ? 'on' : 'off';
		recordTableHeaderToggleEvent( 'product-feed', toggled, status );
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
					},
					{
						key: 'channelVisibility',
						label: __(
							'Channel Visibility',
							'google-listings-and-ads'
						),
						isLeftAligned: true,
						isSortable: true,
					},
					{
						key: 'status',
						label: __( 'Status', 'google-listings-and-ads' ),
						isLeftAligned: true,
						isSortable: true,
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
				totalRows={ total }
				rowsPerPage={ parseInt( query.per_page, 10 ) || 25 }
				summary={ [
					{
						label: __( 'products', 'google-listings-and-ads' ),
						value: total,
					},
				] }
				query={ query }
				onQueryChange={ onQueryChange }
				onColumnsChange={ handleColumnsChange }
			/>
		</div>
	);
};

export default ProductFeedTableCard;
