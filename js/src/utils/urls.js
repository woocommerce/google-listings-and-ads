/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { API_RESPONSE_CODES } from '.~/constants';

export const pagePaths = {
	getStarted: '/google/start',
	setupMC: '/google/setup-mc',
	setupAds: '/google/setup-ads',
	dashboard: '/google/dashboard',
	reports: '/google/reports',
	productFeed: '/google/product-feed',
	settings: '/google/settings',
};

export const subpaths = {
	editFreeListings: '/free-listings/edit',
	editCampaign: '/campaigns/edit',
	createCampaign: '/campaigns/create',
	editPhoneNumber: '/edit-phone-number',
	editStoreAddress: '/edit-store-address',
	reconnectWPComAccount: '/reconnect-wpcom-account',
	reconnectGoogleAccount: '/reconnect-google-account',
};

const getStartedPath = pagePaths.getStarted;
const setupMCPath = pagePaths.setupMC;
const dashboardPath = pagePaths.dashboard;
const settingsPath = pagePaths.settings;
const reportsPath = pagePaths.reports;

export const getEditFreeListingsUrl = () => {
	return getNewPath( { subpath: subpaths.editFreeListings }, dashboardPath );
};

/**
 * Gets the path to the campaign editing page with given query parameters.
 *
 * @param {string} programId The ID of the campaign to be edited.
 * @param {string} [initialStep] The initial step when entering the campaign editing page.
 * @return {string} The path to the campaign editing page with specified query parameters.
 */
export const getEditCampaignUrl = ( programId, initialStep ) => {
	return getNewPath(
		{ subpath: subpaths.editCampaign, programId, initialStep },
		dashboardPath
	);
};

export const getCreateCampaignUrl = () => {
	return getNewPath( { subpath: subpaths.createCampaign }, dashboardPath );
};

export const getGetStartedUrl = () => {
	return getNewPath( null, getStartedPath, null );
};

export const getSetupMCUrl = () => {
	return getNewPath( null, setupMCPath, null );
};

export const getDashboardUrl = ( query = null ) => {
	return getNewPath( query, dashboardPath, null );
};

/**
 * Return product feed URL with query parameters.
 *
 * @param {Object} [query=null] object of params to be updated.
 * @return {string} Product feed URL with specified query parameters.
 */
export const getProductFeedUrl = ( query = null ) => {
	return getNewPath( query, pagePaths.productFeed, null );
};

export const getSettingsUrl = () => {
	return getNewPath( null, settingsPath, null );
};

export const geReportsUrl = () => {
	return getNewPath( null, reportsPath, null );
};

export const getEditPhoneNumberUrl = () => {
	return getNewPath(
		{ subpath: subpaths.editPhoneNumber },
		settingsPath,
		null
	);
};
export const getEditStoreAddressUrl = () => {
	return getNewPath(
		{ subpath: subpaths.editStoreAddress },
		settingsPath,
		null
	);
};

/**
 * Returns the URL of the account re-connecting page.
 *
 * @param {string} code The `code` property of API response.
 * @return {string|undefined} The URL of the account re-connecting page. It returns undefined if the `code` doesn't match any available URLs.
 */
export const getReconnectAccountUrl = ( code ) => {
	let subpath;

	switch ( code ) {
		case API_RESPONSE_CODES.WPCOM_DISCONNECTED:
			subpath = subpaths.reconnectWPComAccount;
			break;
		case API_RESPONSE_CODES.GOOGLE_DISCONNECTED:
			subpath = subpaths.reconnectGoogleAccount;
			break;

		default:
			return;
	}

	return getNewPath( { subpath }, settingsPath, null );
};
