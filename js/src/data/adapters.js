/**
 * @typedef {import('.~/data/actions').Campaign} Campaign
 */

/**
 * Adapts the campaign entity received from API.
 *
 * @param {Object} campaign The campaign entity to be adapted.
 * @return {Campaign} Campaign data.
 */
export function adaptAdsCampaign( campaign ) {
	const allowMultiple = campaign.targeted_locations.length > 0;
	const displayCountries = allowMultiple
		? campaign.targeted_locations
		: [ campaign.country ];
	return {
		...campaign,
		allowMultiple,
		displayCountries,
	};
}
