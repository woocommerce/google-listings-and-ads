/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

const dashboardPath = '/google/dashboard';

export const subpaths = {
	editFreeListings: '/free-listings/edit',
	editCampaign: '/campaigns/edit',
	createCampaign: '/campaigns/create',
};

export const EditFreeListingsUrl = getNewPath(
	{ subpath: subpaths.editFreeListings },
	dashboardPath
);

export const getEditCampaignUrl = ( programId ) => {
	return getNewPath(
		{ subpath: subpaths.editCampaign, programId },
		dashboardPath
	);
};

export const createCampaignUrl = getNewPath(
	{ subpath: subpaths.createCampaign },
	dashboardPath
);
