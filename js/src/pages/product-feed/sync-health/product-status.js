/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PRODUCT_FEED_STATUS_COLORS } from '~/constants';

/**
 * The product sync statuses, in display order. Keys match the `segments` of the
 * product-journey response, the `journey` param, and `sync_status_group` on each
 * product of the product feed, so the overview bar, the table tabs and each row's
 * status always agree. Each has a `key`, a `label`, a Badge `intent`, and a
 * bar `color`.
 *
 * @type {Array<Object>}
 */
export const PRODUCT_STATUSES = [
	{
		key: 'approved',
		label: __( 'Approved', 'google-listings-and-ads' ),
		intent: 'success',
		color: PRODUCT_FEED_STATUS_COLORS.APPROVED,
	},
	{
		key: 'pending_sync',
		label: __( 'Pending sync', 'google-listings-and-ads' ),
		intent: 'info',
		color: PRODUCT_FEED_STATUS_COLORS.PENDING_SYNC,
	},
	{
		key: 'pending_review',
		label: __( 'Pending review', 'google-listings-and-ads' ),
		intent: 'info',
		color: PRODUCT_FEED_STATUS_COLORS.PENDING_REVIEW,
	},
	{
		key: 'disapproved',
		label: __( 'Disapproved', 'google-listings-and-ads' ),
		intent: 'error',
		color: PRODUCT_FEED_STATUS_COLORS.DISAPPROVED,
	},
	{
		key: 'not_synced',
		label: __( 'Not synced', 'google-listings-and-ads' ),
		intent: 'default',
		color: PRODUCT_FEED_STATUS_COLORS.NOT_SYNCED,
	},
];

/**
 * Look up a status by key.
 *
 * @param {string} key Status key.
 * @return {Object|undefined} The status, if known.
 */
export const getProductStatus = ( key ) =>
	PRODUCT_STATUSES.find( ( status ) => status.key === key );
