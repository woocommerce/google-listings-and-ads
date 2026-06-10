/**
 * Internal dependencies
 */
import { ASSET_GROUP_KEY } from '~/constants';

export const STORE_KEY = 'wc/gla';
export const API_NAMESPACE = '/wc/gla';
export const NOTICES_STORE_KEY = 'core/notices';
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
