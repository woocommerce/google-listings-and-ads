/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useCallback, useMemo, useState } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Notice,
	TabPanel,
} from '@wordpress/components';
import { Icon, edit, seen, unseen } from '@wordpress/icons';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { recordGlaEvent, recordTablePageEvent } from '~/utils/tracks';
import AppSpinner from '~/components/app-spinner';
import Badge from '~/components/badge';
import Text from '~/components/app-text';
import useAdminUrl from '~/hooks/useAdminUrl';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '~/data';
import {
	PRODUCT_STATUSES,
	getProductStatus,
} from '../sync-health/product-status';
import { formatCount } from '../sync-health/utils';
import ProductIssuesModal from './product-issues-modal';
import './index.scss';

const PER_PAGE = 10;
const EVENT_CONTEXT = 'product-feed';
const ALL_TAB = 'all';

/**
 * Channel visibility values of the `visibility` query param.
 */
const VISIBILITY = {
	SYNC_AND_SHOW: 'sync-and-show',
	DONT_SYNC_AND_SHOW: 'dont-sync-and-show',
};

const toVisibilityEventProp = ( visible ) =>
	visible ? 'sync_and_show' : 'dont_sync_and_show';

/**
 * Whether a product can be synced at all. A product hidden from the store
 * catalog can't show on any channel, as on the product page, so its channel
 * visibility can't be changed here. A missing `catalog_visible` counts as visible.
 *
 * @param {Object} product Product from the product feed response.
 * @return {boolean} Whether the product is visible in the store catalog.
 */
const isCatalogVisible = ( product ) => product.catalog_visible !== false;

/**
 * Returns whether setting a product's channel visibility to `visible` would
 * change it: the product must be visible in the store catalog, and currently
 * set the other way.
 *
 * @param {boolean} visible `true` for "Sync and show".
 * @return {Function} Takes a product, returns whether the change applies to it.
 */
const canSetVisibility = ( visible ) => ( product ) =>
	isCatalogVisible( product ) && !! product.visible !== visible;

const DEFAULT_VIEW = {
	type: 'table',
	page: 1,
	perPage: PER_PAGE,
	search: '',
	sort: { field: 'title', direction: 'asc' },
	filters: [],
	titleField: 'title',
	fields: [ 'visible', 'sync_status_group', 'issues' ],
	layout: {},
};

/**
 * Product feed query for a DataViews view and a status tab. Search, sorting,
 * filtering and pagination all happen on the server.
 *
 * @param {Object} view DataViews view.
 * @param {string} tab Selected status tab.
 * @return {Object} Query for `getMCProductFeed`.
 */
const toFeedQuery = ( view, tab ) => {
	const query = {
		page: view.page,
		per_page: view.perPage,
		orderby: view.sort?.field || 'title',
		order: view.sort?.direction || 'asc',
	};

	if ( tab !== ALL_TAB ) {
		query.journey = tab;
	}

	const visibility = view.filters?.find(
		( filter ) => filter.field === 'visible' && filter.operator === 'is'
	)?.value;
	if ( visibility ) {
		query.visibility = visibility;
	}

	if ( view.search ) {
		query.search = view.search;
	}

	return query;
};

/**
 * Status tabs, with product counts once they have loaded.
 *
 * @param {Object} [counts] Product-journey response.
 * @return {Array<{ name: string, title: string }>} Tabs for `TabPanel`.
 */
const getTabs = ( counts ) => {
	const withCount = ( label, count ) =>
		counts
			? sprintf(
					// translators: 1: status name, e.g. "Approved", 2: number of products.
					__( '%1$s (%2$s)', 'google-listings-and-ads' ),
					label,
					formatCount( count )
			  )
			: label;

	return [
		{
			name: ALL_TAB,
			title: withCount(
				__( 'All', 'google-listings-and-ads' ),
				counts?.total
			),
		},
		...PRODUCT_STATUSES.map( ( { key, label } ) => ( {
			name: key,
			title: withCount( label, counts?.segments[ key ] ),
		} ) ),
	];
};

/**
 * Triggered when the product feed "bulk edit" functionality is being used
 *
 * @event gla_bulk_edit_click
 * @property {string} context Name of the table
 * @property {number} number_of_items Edit how many items
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
 * Status tabs and the DataViews table of products.
 *
 * @param {Object} props
 * @param {Object} [props.counts] Product counts per status, from product-journey.
 * @param {Function} [props.onVisibilityChange] Called after channel visibility changed, e.g. to reload the counts.
 */
const ProductFeedTable = ( { counts, onVisibilityChange } ) => {
	const { DataViews } = window.wp.dataviews;
	const adminUrl = useAdminUrl();
	const [ tab, setTab ] = useState( ALL_TAB );
	const [ view, setView ] = useState( DEFAULT_VIEW );
	const [ selection, setSelection ] = useState( [] );
	const [ issuesProduct, setIssuesProduct ] = useState( null );
	const { updateMCProductVisibility } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const query = useMemo( () => toFeedQuery( view, tab ), [ view, tab ] );
	const { data, hasFinishedResolution } = useAppSelectDispatch(
		'getMCProductFeed',
		query
	);

	const handleTabSelect = ( name ) => {
		setTab( name );
		setView( ( prev ) => ( { ...prev, page: 1 } ) );
	};

	const handleChangeView = ( newView ) => {
		if ( newView.page !== view.page ) {
			const step = newView.page - view.page;
			let direction = 'goto';
			if ( step === 1 ) {
				direction = 'next';
			} else if ( step === -1 ) {
				direction = 'previous';
			}
			recordTablePageEvent( EVENT_CONTEXT, newView.page, direction );
		}

		setView( newView );
	};

	const fields = useMemo(
		() => [
			{
				id: 'title',
				label: __( 'Product name', 'google-listings-and-ads' ),
				enableHiding: false,
			},
			{
				id: 'visible',
				label: __( 'Channel visibility', 'google-listings-and-ads' ),
				// A product hidden from the store catalog can't be synced, so it
				// reads as "Don't sync and show" whatever its setting.
				getValue: ( { item } ) =>
					item.visible && isCatalogVisible( item )
						? VISIBILITY.SYNC_AND_SHOW
						: VISIBILITY.DONT_SYNC_AND_SHOW,
				elements: [
					{
						value: VISIBILITY.SYNC_AND_SHOW,
						label: __( 'Sync and show', 'google-listings-and-ads' ),
					},
					{
						value: VISIBILITY.DONT_SYNC_AND_SHOW,
						label: __(
							'Don’t sync and show',
							'google-listings-and-ads'
						),
					},
				],
				filterBy: { operators: [ 'is' ] },
				// Not sortable: `orderby=visible` only returns products that have
				// the visibility meta, so the rows wouldn't match the tab count.
				enableSorting: false,
			},
			{
				id: 'sync_status_group',
				label: __( 'Status', 'google-listings-and-ads' ),
				// Not sortable: the server sorts `status` by Merchant Center status,
				// which isn't the status shown here.
				enableSorting: false,
				render: ( { item } ) => {
					const status = getProductStatus( item.sync_status_group );
					return status ? (
						<Badge intent={ status.intent }>{ status.label }</Badge>
					) : null;
				},
			},
			{
				id: 'issues',
				label: __( 'Issues', 'google-listings-and-ads' ),
				type: 'integer',
				enableSorting: false,
				getValue: ( { item } ) => item.issues?.length || 0,
				render: ( { item } ) => {
					const count = item.issues?.length || 0;

					if ( ! count ) {
						return formatCount( 0 );
					}

					return (
						<Button
							variant="link"
							aria-label={ sprintf(
								// translators: 1: number of issues, 2: product name.
								_n(
									'View %1$s issue for %2$s',
									'View %1$s issues for %2$s',
									count,
									'google-listings-and-ads'
								),
								formatCount( count ),
								item.title
							) }
							onClick={ () => setIssuesProduct( item ) }
						>
							{ sprintf(
								// translators: %s: number of issues.
								_n(
									'%s issue',
									'%s issues',
									count,
									'google-listings-and-ads'
								),
								formatCount( count )
							) }
						</Button>
					);
				},
			},
		],
		[]
	);

	/**
	 * Sets the channel visibility of the selected products it would change,
	 * then confirms it, clears the selection, and reloads the counts. Does
	 * nothing when none would change. The table reloads on its own, since the
	 * store invalidates the product feed after the update.
	 *
	 * @param {Array<Object>} items Selected products.
	 * @param {boolean} visible `true` for "Sync and show".
	 * @return {Promise} Resolves when done, so callers can show progress.
	 */
	const changeVisibility = useCallback(
		( items, visible ) => {
			// DataViews 4.22 passes every selected item to bulk actions, so keep
			// only those the change applies to.
			const ids = items
				.filter( canSetVisibility( visible ) )
				.map( ( item ) => item.id );

			if ( ! ids.length ) {
				return Promise.resolve();
			}

			recordGlaEvent( 'gla_bulk_edit_click', {
				context: EVENT_CONTEXT,
				number_of_items: ids.length,
				visibility_to: toVisibilityEventProp( visible ),
			} );

			return updateMCProductVisibility( ids, visible ).then(
				() => {
					createNotice(
						'success',
						sprintf(
							// translators: %d: number of products are updated successfully, with minimum value of 1.
							_n(
								'You successfully changed the channel visibility of %d product',
								'You successfully changed the channel visibility of %d products',
								ids.length,
								'google-listings-and-ads'
							),
							ids.length
						)
					);
					setSelection( [] );
					onVisibilityChange?.();
				},
				// The store action already shows an error notice. The endpoint saves
				// what it can before reporting failures, so reload when some succeeded.
				( error ) => {
					if ( error?.success?.length ) {
						onVisibilityChange?.();
					}
				}
			);
		},
		[ updateMCProductVisibility, createNotice, onVisibilityChange ]
	);

	const actions = useMemo( () => {
		return [
			{
				id: 'edit',
				label: __( 'Edit', 'google-listings-and-ads' ),
				icon: <Icon icon={ edit } width={ 24 } height={ 24 } />,
				isPrimary: true,
				callback: ( [ product ] ) => {
					recordGlaEvent( 'gla_edit_product_click', {
						status: product.status,
						visibility: toVisibilityEventProp(
							product.visible && isCatalogVisible( product )
						),
					} );
					window.open(
						addQueryArgs( `${ adminUrl }post.php`, {
							post: product.id,
							action: 'edit',
						} ),
						'_blank',
						'noopener,noreferrer'
					);
				},
			},
			// Bulk actions, each eligible only for products it would change, so a
			// row's actions menu offers just the opposite of its current setting.
			// Products hidden from the store catalog get neither, which also makes
			// their rows unselectable. DataViews 4.22 shows them in its selection
			// footer as icon buttons labelled with the action name, so they need icons.
			{
				id: 'sync-and-show',
				label: __( 'Sync and show', 'google-listings-and-ads' ),
				icon: seen,
				supportsBulk: true,
				isEligible: canSetVisibility( true ),
				callback: ( items ) => changeVisibility( items, true ),
			},
			{
				id: 'dont-sync-and-show',
				label: __( 'Don’t sync and show', 'google-listings-and-ads' ),
				icon: unseen,
				supportsBulk: true,
				isEligible: canSetVisibility( false ),
				callback: ( items ) => changeVisibility( items, false ),
			},
		];
	}, [ adminUrl, changeVisibility ] );

	const products = data?.products || [];
	const total = data?.total || 0;

	return (
		<>
			<TabPanel
				className="gla-product-feed-table-card__tabs"
				tabs={ getTabs( counts ) }
				initialTabName={ ALL_TAB }
				onSelect={ handleTabSelect }
			>
				{ () => (
					<>
						{ hasFinishedResolution && ! data?.products && (
							<Notice status="error" isDismissible={ false }>
								{ __(
									'An error occurred while retrieving products. Please try again later.',
									'google-listings-and-ads'
								) }
							</Notice>
						) }
						<DataViews
							getItemId={ ( item ) => String( item.id ) }
							fields={ fields }
							actions={ actions }
							selection={ selection }
							onChangeSelection={ setSelection }
							data={ products }
							view={ view }
							onChangeView={ handleChangeView }
							paginationInfo={ {
								totalItems: total,
								totalPages: Math.ceil( total / view.perPage ),
							} }
							defaultLayouts={ { table: {} } }
							searchLabel={ __(
								'Search products',
								'google-listings-and-ads'
							) }
							isLoading={ ! hasFinishedResolution }
						/>
					</>
				) }
			</TabPanel>

			{ issuesProduct && (
				<ProductIssuesModal
					product={ issuesProduct }
					onRequestClose={ () => setIssuesProduct( null ) }
				/>
			) }
		</>
	);
};

/**
 * Product Feed table: products by sync status, with their channel visibility and
 * issues, an action to edit each one, and bulk actions to set channel visibility.
 *
 * @param {Object} props
 * @param {Object} [props.counts] Product counts per status, from product-journey.
 * @param {Function} [props.onVisibilityChange] Called after channel visibility changed, e.g. to reload the counts.
 *
 * @fires gla_bulk_edit_click with `context: 'product-feed'`
 * @fires gla_edit_product_click
 * @fires gla_table_go_to_page with `context: 'product-feed'`
 * @fires gla_table_page_click with `context: 'product-feed'`
 */
const ProductFeedTableCard = ( { counts, onVisibilityChange } ) => {
	const dataViewsStatus = useDataViewsScript();

	return (
		<Card className="gla-product-feed-table-card">
			<CardHeader>
				<Text variant="title-small" as="h2">
					{ __( 'Product Feed', 'google-listings-and-ads' ) }
				</Text>
			</CardHeader>
			<CardBody>
				{ dataViewsStatus === 'loading' && <AppSpinner /> }
				{ dataViewsStatus === 'failed' && (
					<Notice status="error" isDismissible={ false }>
						{ __(
							'There was an error loading the product feed.',
							'google-listings-and-ads'
						) }
					</Notice>
				) }
				{ dataViewsStatus === 'ready' && (
					<ProductFeedTable
						counts={ counts }
						onVisibilityChange={ onVisibilityChange }
					/>
				) }
			</CardBody>
		</Card>
	);
};

export default ProductFeedTableCard;
