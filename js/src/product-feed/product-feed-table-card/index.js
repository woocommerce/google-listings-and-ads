/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CheckboxControl, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import EditProductLink from '.~/components/edit-product-link';
import AppTableCard from '.~/components/app-table-card';
import './index.scss';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import AppSpinner from '.~/components/app-spinner';
import statusLabelMap from './statusLabelMap';

const PER_PAGE = 10;

/**
 * Product Feed table.
 *
 * @see AppTableCard
 *
 * @param {Object} [props] Properties to be forwarded to AppTableCard.
 */
const ProductFeedTableCard = ( props ) => {
	const [ selectedRows, setSelectedRows ] = useState( new Set() );
	const [ query, setQuery ] = useState( {
		page: 1,
		per_page: PER_PAGE,
		orderby: 'title',
		order: 'asc',
	} );
	const { hasFinishedResolution, data } = useAppSelectDispatch(
		'getMCProductFeed',
		query
	);

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	const { products, total } = data;

	// TODO: what happens upon clicking the Edit Visibility button.
	const handleEditVisibilityClick = () => {};

	const handleSelectAllCheckboxChange = ( checked ) => {
		if ( checked ) {
			const ids = products.map( ( el ) => el.id );
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
			<AppTableCard
				title={ __( 'Product Feed', 'google-listings-and-ads' ) }
				actions={
					<Button
						isSecondary
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
				}
				headers={ [
					{
						key: 'select',
						label: (
							<CheckboxControl
								checked={
									selectedRows.size === products.length
								}
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
				rows={ products.map( ( el ) => {
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
						{
							display: el.visible
								? __(
										'Sync and show',
										'google-listings-and-ads'
								  )
								: __(
										`Don't sync and show`,
										'google-listings-and-ads'
								  ),
						},
						{
							display: statusLabelMap[ el.status ],
						},
						{
							display: <EditProductLink productId={ el.id } />,
						},
					];
				} ) }
				totalRows={ total }
				rowsPerPage={ query.per_page }
				summary={ [
					{
						label: __( 'products', 'google-listings-and-ads' ),
						value: total,
					},
				] }
				{ ...props }
			/>
		</div>
	);
};

export default ProductFeedTableCard;
