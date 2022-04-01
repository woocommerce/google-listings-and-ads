/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { API_RESPONSE_CODES } from '.~/constants';

const getStartedUrl = '/google/start';
const dashboardPath = '/google/dashboard';
const settingsPath = '/google/settings';

export const subpaths = {
	editFreeListings: '/free-listings/edit',
	editCampaign: '/campaigns/edit',
	createCampaign: '/campaigns/create',
	editPhoneNumber: '/edit-phone-number',
	editStoreAddress: '/edit-store-address',
	reconnectWPComAccount: '/reconnect-wpcom-account',
	reconnectGoogleAccount: '/reconnect-google-account',
};

export const getEditFreeListingsUrl = () => {
	return getNewPath( { subpath: subpaths.editFreeListings }, dashboardPath );
};

export const getEditCampaignUrl = ( programId ) => {
	return getNewPath(
		{ subpath: subpaths.editCampaign, programId },
		dashboardPath
	);
};

export const getCreateCampaignUrl = () => {
	return getNewPath( { subpath: subpaths.createCampaign }, dashboardPath );
};

export const getGetStartedUrl = () => {
	return getNewPath( null, getStartedUrl, null );
};

export const getDashboardUrl = () => {
	return getNewPath( null, dashboardPath, null );
};

export const getSettingsUrl = () => {
	return getNewPath( null, settingsPath, null );
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
