/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_RESPONSE_CODES } from '~/constants';

// The paths 'setup-mc' and 'setup-ads' came from its original page name.
// It's currently retained to ensure paths that might be
// externally referenced won't be invalidated.
export const pagePaths = {
	getStarted: '/google/start',
	onboarding: '/google/setup-mc',
	adsOnboarding: '/google/setup-ads',
	dashboard: '/google/dashboard',
	reports: '/google/reports',
	productFeed: '/google/product-feed',
	settings: '/google/settings',
};

export const subpaths = {
	editCampaign: '/campaigns/edit',
	createCampaign: '/campaigns/create',
	editStoreAddress: '/edit-store-address',
	reconnectWPComAccount: '/reconnect-wpcom-account',
	reconnectGoogleAccount: '/reconnect-google-account',
};

const getStartedPath = pagePaths.getStarted;
const onboardingPath = pagePaths.onboarding;
const dashboardPath = pagePaths.dashboard;
const settingsPath = pagePaths.settings;
const reportsPath = pagePaths.reports;
const GOOGLE_ADS_OVERVIEW_URL = 'https://ads.google.com/aw/overview';
const YOUTUBE_CHANNEL_BASE_URL = 'https://www.youtube.com/channel/';

/**
 * Gets the path to the campaign editing page with given query parameters.
 *
 * @param {string} programId The ID of the campaign to be edited.
 * @param {string} [initialStep] The initial step when entering the campaign editing page.
 * @return {string} The path to the campaign editing page with specified query parameters.
 */
export const getEditCampaignUrl = ( programId, initialStep ) => {
	return getNewPath(
		{ subpath: subpaths.editCampaign, programId, step: initialStep },
		dashboardPath
	);
};

export const getCreateCampaignUrl = () => {
	return getNewPath(
		{ subpath: subpaths.createCampaign },
		dashboardPath,
		null
	);
};

export const getGetStartedUrl = () => {
	return getNewPath( null, getStartedPath, null );
};

export const getOnboardingUrl = () => {
	return getNewPath( null, onboardingPath, null );
};

export const getSetupAdsUrl = () => {
	return getNewPath( null, pagePaths.adsOnboarding, null );
};

export const getDashboardUrl = ( query = null ) => {
	return getNewPath( query, dashboardPath, null );
};

/**
 * Return the Google Ads overview URL.
 *
 * @return {string} Google Ads overview URL.
 */
export const getGoogleAdsOverviewUrl = () => {
	return GOOGLE_ADS_OVERVIEW_URL;
};

/**
 * Build the public YouTube channel URL for a connected channel.
 *
 * @param {{ id?: string|null }} [channel] Connected YouTube channel data.
 * @return {string} YouTube channel URL.
 */
export const getYouTubeChannelUrl = ( channel ) => {
	if ( ! channel?.id ) {
		return YOUTUBE_CHANNEL_BASE_URL;
	}

	return `${ YOUTUBE_CHANNEL_BASE_URL }${ channel.id }`;
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

/**
 * Return the Settings URL with optional query parameters.
 *
 * @param {Object|null} [query=null] Query parameters to include.
 * @return {string} Settings URL.
 */
export const getSettingsUrl = ( query = null ) => {
	return getNewPath( query, settingsPath, null );
};

/**
 * Returns the URL of the accounts settings page.
 *
 * @return {string} The URL of the accounts settings page.
 */
export const getAccountsSettingsUrl = () => {
	return getNewPath( { section: 'accounts' }, settingsPath, null );
};

export const getWCTrackingSettingsUrl = () => {
	return addQueryArgs( 'admin.php', {
		page: 'wc-settings',
		tab: 'advanced',
		section: 'woocommerce_com',
	} );
};

export const getShippingUrl = () => {
	return getNewPath( null, pagePaths.shipping, null );
};

export const geReportsUrl = () => {
	return getNewPath( null, reportsPath, null );
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

/**
 * Returns the URL of the WooCommerce coupons index page.
 *
 * @return {string} The URL of the coupons index page.
 */
export const getWCCouponsUrl = () => {
	return addQueryArgs( 'edit.php', {
		post_type: 'shop_coupon',
	} );
};
