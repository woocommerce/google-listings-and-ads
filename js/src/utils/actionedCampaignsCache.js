/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS, DAY_IN_SECONDS } from '~/constants';
import localStorage from '~/utils/localStorage';

/**
 * Retrieves the list of campaign IDs that have been actioned and are still valid (not expired).
 *
 * This function reads the serialized campaigns from localStorage, parses them,
 * filters out expired campaigns, updates localStorage if any expired campaigns are removed,
 * and returns an array of valid campaign IDs.
 *
 * @return {string[]} Array of valid campaign IDs.
 */
export const getActionedCampaigns = () => {
	const serializedCampaigns = localStorage.get(
		LOCAL_STORAGE_KEYS.RAISE_BUDGET_RECOMMENDATIONS_ACTIONED_CAMPAIGNS
	);
	let campaigns = {};

	if ( serializedCampaigns ) {
		try {
			campaigns = JSON.parse( serializedCampaigns );
		} catch ( e ) {
			campaigns = {};
		}
	}

	if ( Object.keys( campaigns ).length === 0 ) {
		return [];
	}

	const now = Date.now();
	const validCampaigns = {};
	for ( const [ id, expiry ] of Object.entries( campaigns ) ) {
		if ( expiry > now ) {
			validCampaigns[ id ] = expiry;
		}
	}
	if (
		Object.keys( validCampaigns ).length !== Object.keys( campaigns ).length
	) {
		localStorage.set(
			LOCAL_STORAGE_KEYS.RAISE_BUDGET_RECOMMENDATIONS_ACTIONED_CAMPAIGNS,
			JSON.stringify( validCampaigns )
		);
	}

	return Object.keys( validCampaigns );
};

/**
 * Adds or updates a campaign's actioned status in localStorage with an expiry timestamp.
 *
 * Retrieves the current list of actioned campaigns from localStorage, updates the expiry for the specified campaign,
 * and saves the updated list back to localStorage.
 *
 * @param {string} campaignId - The unique identifier of the campaign to upsert.
 */
export const upsertActionedCampaign = ( campaignId ) => {
	const serializedCampaigns = localStorage.get(
		LOCAL_STORAGE_KEYS.RAISE_BUDGET_RECOMMENDATIONS_ACTIONED_CAMPAIGNS
	);
	let campaigns = {};

	if ( serializedCampaigns ) {
		try {
			campaigns = JSON.parse( serializedCampaigns );
		} catch ( e ) {
			campaigns = {};
		}
	}

	const expiry = Date.now() + 1 * DAY_IN_SECONDS * 1000;
	campaigns[ campaignId ] = expiry;

	localStorage.set(
		LOCAL_STORAGE_KEYS.RAISE_BUDGET_RECOMMENDATIONS_ACTIONED_CAMPAIGNS,
		JSON.stringify( campaigns )
	);
};

window.zoe = upsertActionedCampaign;
