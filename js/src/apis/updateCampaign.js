/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

const updateCampaign = ( id, data ) => {
	return apiFetch( {
		path: `/wc/gla/ads/campaigns/${ id }`,
		method: 'POST',
		data,
	} );
};

export default updateCampaign;
