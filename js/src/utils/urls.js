/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

const dashboardPath = '/google/dashboard';

export const subpaths = {
	editFreeListings: '/free-listings/edit',
	editCampaign: '/campaigns/edit',
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
