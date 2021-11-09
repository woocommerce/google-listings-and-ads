/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

const getStartedUrl = '/google/start';
const dashboardPath = '/google/dashboard';
const settingsPath = '/google/settings';

export const subpaths = {
	editFreeListings: '/free-listings/edit',
	editCampaign: '/campaigns/edit',
	createCampaign: '/campaigns/create',
	editPhoneNumber: '/edit-phone-number',
	editStoreAddress: '/edit-store-address',
	reconnectAccounts: '/reconnect-accounts',
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

export const getReconnectAccountsUrl = () => {
	return getNewPath(
		{ subpath: subpaths.reconnectAccounts },
		settingsPath,
		null
	);
};
