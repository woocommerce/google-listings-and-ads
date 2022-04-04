/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	CheckboxControl,
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	__experimentalText as Text,
} from '@wordpress/components';
import {
	EmptyTable,
	Pagination,
	Table,
	TablePlaceholder,
} from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import recordEvent, { recordTablePageEvent } from '.~/utils/recordEvent';
import AppTableCardDiv from '.~/components/app-table-card-div';
import EditProductLink from '.~/components/edit-product-link';
import './index.scss';
import { useAppDispatch } from '.~/data';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import EditVisibilityAction from './edit-visibility-action';
import statusLabelMap from './statusLabelMap';

const PER_PAGE = 10;
const EVENT_CONTEXT = 'product-feed';
const toVisibilityEventProp = ( visible ) =>
	visible ? 'sync_and_show' : 'dont_sync_and_show';

/**
 * Triggered when the product feed "bulk edit" functionality is being used
 *
 * @event gla_bulk_edit_click
 * @property {string} context name of the table
 * @property {number} number_of_items edit how many items
 * @property {string} visibility_to `("sync_and_show" | "dont_sync_and_show")`
 */

/**
 * Triggered when edit links are clicked from product feed table.
 *
 * @event gla_edit_product_click
 * @property {string} status `("approved" | "partially_approved" | "expiring" | "pending" | "disapproved" | "not_synced")`
 * @property {string} visibility `("sync_and_show" | "dont_sync_and_show")`
 */

/**
 * Product Feed table.
 *
 * @fires gla_bulk_edit_click with `context: 'product-feed'`
 * @fires gla_edit_product_click
 * @fires gla_table_go_to_page with `context: 'product-feed'`
 * @fires gla_table_page_click with `context: 'product-feed'`
 */
const ProductFeedTableCard = () => {
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
	const { updateMCProductVisibility } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const handleSelectAllCheckboxChange = ( checked ) => {
		if ( checked ) {
			const ids = data?.products.map( ( el ) => el.id );
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

	const handlePageChange = ( newPage, direction ) => {
		setQuery( {
			...query,
			page: newPage,
		} );
		recordTablePageEvent( EVENT_CONTEXT, newPage, direction );
	};

	const handleSort = ( orderby, order ) => {
		setQuery( {
			...query,
			orderby,
			order,
		} );
	};

	const headers = [
		{
			key: 'select',
			label: (
				<CheckboxControl
					disabled={ ! data?.products }
					checked={
						data?.products?.length > 0 &&
						data?.products?.every( ( el ) =>
							selectedRows.has( el.id )
						)
					}
					onChange={ handleSelectAllCheckboxChange }
				/>
			),
			isLeftAligned: true,
			required: true,
		},
		{
			key: 'title',
			label: __( 'Product Title', 'google-listings-and-ads' ),
			isLeftAligned: true,
			required: true,
			isSortable: true,
		},
		{
			key: 'visible',
			label: __( 'Channel Visibility', 'google-listings-and-ads' ),
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
	];

	const handleEditVisibilityClick = ( visible ) => {
		const ids = Array.from( selectedRows );
		const { length } = ids;

		updateMCProductVisibility( ids, visible ).then( () => {
			const message = sprintf(
				// translators: %d: number of products are updated successfully, with minimum value of 1.
				_n(
					'You successfully changed the channel visibility of %d product',
					'You successfully changed the channel visibility of %d products',
					length,
					'google-listings-and-ads'
				),
				length
			);
			createNotice( 'success', message );
		} );

		recordEvent( 'gla_bulk_edit_click', {
			context: EVENT_CONTEXT,
			number_of_items: length,
			visibility_to: toVisibilityEventProp( visible ),
		} );

		handleSelectAllCheckboxChange( false );
	};

	const actions = (
		<EditVisibilityAction
			selectedSize={ selectedRows.size }
			onActionClick={ handleEditVisibilityClick }
		/>
	);

	return (
		<AppTableCardDiv className="gla-product-feed-table-card">
			<Card
				className={ classnames( 'woocommerce-table', {
					'has-actions': !! actions,
				} ) }
			>
				<CardHeader>
					{ /* We use this Text component to make it similar to TableCard component. */ }
					<Text variant="title.small" as="h2">
						{ __( 'Product Feed', 'google-listings-and-ads' ) }
					</Text>
					{ /* This is also similar to TableCard component implementation. */ }
					<div className="woocommerce-table__actions">
						{ actions }
					</div>
				</CardHeader>
				<CardBody size={ null }>
					{ ! hasFinishedResolution && (
						<TablePlaceholder
							headers={ headers }
							numberOfRows={ query.per_page }
						/>
					) }
					{ hasFinishedResolution && ! data?.products && (
						<EmptyTable headers={ headers } numberOfRows={ 1 }>
							{ __(
								'An error occurred while retrieving products. Please try again later.',
								'google-listings-and-ads'
							) }
						</EmptyTable>
					) }
					{ hasFinishedResolution && data?.products && (
						<Table
							headers={ headers }
							rows={ data.products.map( ( el ) => {
								return [
									{
										display: (
											<CheckboxControl
												checked={ selectedRows.has(
													el.id
												) }
												onChange={ getHandleSelectRowCheckboxChange(
													el.id
												) }
											/>
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
										display: (
											<EditProductLink
												productId={ el.id }
												eventName="gla_edit_product_click"
												eventProps={ {
													status: el.status,
													visibility: toVisibilityEventProp(
														el.visible
													),
												} }
											/>
										),
									},
								];
							} ) }
							query={ query }
							onSort={ handleSort }
						/>
					) }
				</CardBody>
				<CardFooter justify="center">
					{ data?.total && (
						<Pagination
							page={ query.page }
							perPage={ query.per_page }
							total={ data?.total }
							showPagePicker={ true }
							showPerPagePicker={ false }
							onPageChange={ handlePageChange }
						/>
					) }
				</CardFooter>
			</Card>
		</AppTableCardDiv>
	);
};

export default ProductFeedTableCard;
