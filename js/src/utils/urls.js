/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

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

export const getDashboardUrl = () => {
	return getNewPath( null, dashboardPath, null );
};

export const getSettingsUrl = () => {
	return getNewPath( null, settingsPath, null );
};

export const getEditPhoneNumberUrl = () => {
	return getNewPath( null, settingsPath + subpaths.editPhoneNumber, null );
};
export const getEditStoreAddressUrl = () => {
	return getNewPath( null, settingsPath + subpaths.editStoreAddress, null );
};

export const getReconnectAccountsUrl = () => {
	return getNewPath( null, settingsPath + subpaths.reconnectAccounts, null );
};
