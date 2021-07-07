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

export const getReconnectAccountsUrl = () => {
	return getNewPath(
		{ subpath: subpaths.reconnectAccounts },
		settingsPath,
		null
	);
};
