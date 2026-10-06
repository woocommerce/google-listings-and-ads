/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { dateI18n, humanTimeDiff } from '@wordpress/date';
import { addQueryArgs } from '@wordpress/url';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import { getProductFeedUrl, getSettingsUrl } from '~/utils/urls';

/**
 * Badge intent for each pillar status.
 */
export const STATUS_INTENT = {
	ok: 'success',
	syncing: 'info',
	warning: 'warning',
	error: 'error',
	paused: 'warning',
	disconnected: 'error',
	unknown: 'default',
};

/**
 * Short label for each pillar status.
 */
export const STATUS_LABEL = {
	ok: __( 'Healthy', 'google-listings-and-ads' ),
	syncing: __( 'Syncing', 'google-listings-and-ads' ),
	warning: __( 'Needs attention', 'google-listings-and-ads' ),
	error: __( 'Error', 'google-listings-and-ads' ),
	paused: __( 'Paused', 'google-listings-and-ads' ),
	disconnected: __( 'Disconnected', 'google-listings-and-ads' ),
	unknown: __( 'No data yet', 'google-listings-and-ads' ),
};

/**
 * Badge intent and label for each background job state.
 */
export const JOB_STATE = {
	ok: {
		intent: 'success',
		label: __( 'OK', 'google-listings-and-ads' ),
	},
	scheduled: {
		intent: 'default',
		label: __( 'Scheduled', 'google-listings-and-ads' ),
	},
	queued: {
		intent: 'info',
		label: __( 'Queued', 'google-listings-and-ads' ),
	},
	running: {
		intent: 'info',
		label: __( 'Running', 'google-listings-and-ads' ),
	},
	errors: {
		intent: 'warning',
		label: __( 'Recent failures', 'google-listings-and-ads' ),
	},
	delayed: {
		intent: 'warning',
		label: __( 'Delayed', 'google-listings-and-ads' ),
	},
	stuck: {
		intent: 'error',
		label: __( 'Stuck', 'google-listings-and-ads' ),
	},
	stalled: {
		intent: 'error',
		label: __( 'Not running', 'google-listings-and-ads' ),
	},
	interrupted: {
		intent: 'error',
		label: __( 'Interrupted', 'google-listings-and-ads' ),
	},
	paused: {
		intent: 'error',
		label: __( 'Paused', 'google-listings-and-ads' ),
	},
};

/**
 * Relative time like "5 minutes ago", or a fallback when missing.
 *
 * @param {number|null} timestamp Unix timestamp in seconds.
 * @param {string} [fallback]
 * @return {string} Formatted time.
 */
export const timeAgo = (
	timestamp,
	fallback = __( 'Never', 'google-listings-and-ads' )
) => {
	if ( ! timestamp ) {
		return fallback;
	}

	return humanTimeDiff( timestamp * 1000 );
};

/**
 * Absolute local date and time, for tooltips and diagnostics.
 *
 * @param {number|null} timestamp Unix timestamp in seconds.
 * @return {string} Formatted date, or an empty string.
 */
export const formatDateTime = ( timestamp ) =>
	timestamp ? dateI18n( 'Y-m-d H:i', timestamp * 1000 ) : '';

/**
 * Localized integer.
 *
 * @param {number} value
 * @return {string} Formatted number.
 */
export const formatCount = ( value ) => Number( value || 0 ).toLocaleString();

/**
 * Link to the Action Scheduler admin list filtered by hook and status.
 *
 * @param {string} search Hook, or hook prefix such as `gla/jobs/update_products/`.
 * @param {string} [status] Action Scheduler status.
 * @return {string} Admin URL.
 */
export const getScheduledActionsUrl = ( search, status ) =>
	addQueryArgs( `${ getSetting( 'adminUrl' ) }admin.php`, {
		page: 'wc-status',
		tab: 'action-scheduler',
		s: search,
		status,
		orderby: 'schedule',
		order: 'desc',
	} );

/**
 * Paths of plugin pages the health copy links to.
 */
export const PAGE_URL = {
	productFeed: getProductFeedUrl(),
	settings: getSettingsUrl(),
};

/**
 * Describe one reason from the API as a sentence and an optional action.
 *
 * @param {Object} reason One entry of `reasons` from the sync-health response.
 * @param {string} reason.code Reason code.
 * @param {Object} [reason.data] Values the copy interpolates.
 * @return {{ text: string, action?: { label: string, url: string } }} Copy for the reason.
 */
export const describeReason = ( { code, data = {} } ) => {
	switch ( code ) {
		case 'google_disconnected':
			return {
				text: __(
					'Your Google Merchant Center account is not connected, so nothing is being sent to Google.',
					'google-listings-and-ads'
				),
				action: {
					label: __( 'Open settings', 'google-listings-and-ads' ),
					url: PAGE_URL.settings,
				},
			};
		case 'jetpack_disconnected':
			return {
				text: __(
					'Your WordPress.com connection is missing an owner, so requests to Google are rejected.',
					'google-listings-and-ads'
				),
				action: {
					label: __( 'Open settings', 'google-listings-and-ads' ),
					url: PAGE_URL.settings,
				},
			};
		case 'auth_paused':
			return {
				text: sprintf(
					// translators: %s: relative time, e.g. "in 20 minutes".
					__(
						'WordPress.com rejected a request, so syncing is paused. It retries automatically %s.',
						'google-listings-and-ads'
					),
					timeAgo( data.retry_at, '' )
				),
			};
		case 'url_mismatch':
			return {
				text: __(
					'Your store URL no longer matches the URL claimed in Merchant Center, so syncing is turned off.',
					'google-listings-and-ads'
				),
				action: {
					label: __( 'Open settings', 'google-listings-and-ads' ),
					url: PAGE_URL.settings,
				},
			};
		case 'runner_stalled':
			return {
				text: sprintf(
					// translators: %s: relative time, e.g. "2 hours ago".
					__(
						'Background jobs have been waiting to run since %s. WP-Cron may be disabled on this site.',
						'google-listings-and-ads'
					),
					timeAgo( data.since )
				),
				action: {
					label: __(
						'View scheduled actions',
						'google-listings-and-ads'
					),
					url: getScheduledActionsUrl( 'gla/jobs/', 'pending' ),
				},
			};
		case 'product_job_paused':
			return {
				text: __(
					'Product sync stopped after failing several times in a row.',
					'google-listings-and-ads'
				),
				action: {
					label: __( 'View failed jobs', 'google-listings-and-ads' ),
					url: getScheduledActionsUrl( 'gla/jobs/', 'failed' ),
				},
			};
		case 'full_sync_interrupted':
			return {
				text: __(
					'The last full product sync stopped before it finished.',
					'google-listings-and-ads'
				),
				action: {
					label: __( 'View failed jobs', 'google-listings-and-ads' ),
					url: getScheduledActionsUrl(
						'gla/jobs/update_all_products/',
						'failed'
					),
				},
			};
		case 'product_job_stuck':
			return {
				text: __(
					'A product sync job has been running much longer than expected.',
					'google-listings-and-ads'
				),
				action: {
					label: __( 'View running jobs', 'google-listings-and-ads' ),
					url: getScheduledActionsUrl( 'gla/jobs/', 'in-progress' ),
				},
			};
		case 'products_stuck_pending':
			return {
				text: sprintf(
					// translators: %s: number of products.
					__(
						'%s products are marked as waiting to sync, but nothing is scheduled to send them.',
						'google-listings-and-ads'
					),
					formatCount( data.count )
				),
				action: {
					label: __( 'Open Product Feed', 'google-listings-and-ads' ),
					url: PAGE_URL.productFeed,
				},
			};
		case 'shipping_job_paused':
		case 'shipping_failed':
			return {
				text: __(
					'Your shipping settings could not be updated in Google.',
					'google-listings-and-ads'
				),
				action: {
					label: __(
						'Review shipping settings',
						'google-listings-and-ads'
					),
					url: PAGE_URL.settings,
				},
			};
		case 'review_refresh_failed':
			return {
				text: __(
					'Google review results could not be fetched.',
					'google-listings-and-ads'
				),
				action: {
					label: __( 'Open Product Feed', 'google-listings-and-ads' ),
					url: PAGE_URL.productFeed,
				},
			};
		case 'review_data_stale':
			return {
				text: sprintf(
					// translators: %s: relative time, e.g. "2 days ago".
					__(
						'Google review results were last fetched %s.',
						'google-listings-and-ads'
					),
					timeAgo( data.data_at )
				),
			};
		case 'products_backoff':
			return {
				text: sprintf(
					// translators: %s: number of products.
					__(
						'%s products hit repeated temporary errors and are paused for up to 3 hours. They retry automatically.',
						'google-listings-and-ads'
					),
					formatCount( data.count )
				),
			};
		default:
			return { text: code };
	}
};
