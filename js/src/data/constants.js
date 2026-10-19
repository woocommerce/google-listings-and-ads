/**
 * Internal dependencies
 */
import { ASSET_GROUP_KEY } from '~/constants';

export const STORE_KEY = 'wc/gla';
export const API_NAMESPACE = '/wc/gla';
export const NOTICES_STORE_KEY = 'core/notices';

/**
 * Product feed query args that filter the result set. Changing any of them
 * changes which products each page holds, so stored pages must be discarded.
 */
export const PRODUCT_FEED_FILTERS = [ 'journey', 'visibility', 'search' ];

/**
 * Whether two product feed queries (or a query and the stored feed) use the
 * same filters. A missing filter is the same as an empty one.
 *
 * @param {Object} [a] Query or stored product feed.
 * @param {Object} [b] Query or stored product feed.
 * @return {boolean} True when every filter matches.
 */
export const isSameProductFeedFilter = ( a = {}, b = {} ) =>
	PRODUCT_FEED_FILTERS.every(
		( key ) => ( a[ key ] || '' ) === ( b[ key ] || '' )
	);
export const REQUEST_ACTIONS = {
	DELETE: 'DELETE',
	POST: 'POST',
};

export const EMPTY_ASSET_ENTITY_GROUP = {
	assets: {},
	[ ASSET_GROUP_KEY.FINAL_URL ]: '',
	[ ASSET_GROUP_KEY.DISPLAY_URL_PATH ]: [],
};

export const ERROR_SLOTS = {
	GOOGLE_MC_CONNECTION_ERROR_SLOT: 'setup-mc-google_mc_connection',
	GOOGLE_ADS_CONNECTION_ERROR_SLOT: 'setup-ads-google_ads_connection',
};
