/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS, DAY_IN_SECONDS } from '~/constants';
import localStorage from '~/utils/localStorage';

const useActionedCampaignsCache = (
	storageKey = LOCAL_STORAGE_KEYS.RAISE_BUDGET_RECOMMENDATIONS_ACTIONED_CAMPAIGNS,
	ttl = 1 * DAY_IN_SECONDS * 1000
) => {
	const [ cachedCampaigns, setCachedCampaigns ] = useState( [] );

	/**
	 * Retrieves the list of valid campaigns from localStorage.
	 * Filters out expired campaigns based on their expiry timestamp.
	 * Updates localStorage if any expired campaigns are removed.
	 *
	 * @return {Array<Object>} An array of valid campaign objects.
	 */
	const getActionedCampaigns = useCallback( () => {
		const serializedCampaigns = localStorage.get( storageKey );
		let campaigns = [];

		if ( serializedCampaigns ) {
			try {
				campaigns = JSON.parse( serializedCampaigns );
			} catch ( e ) {
				campaigns = [];
			}
		}

		if ( campaigns.length === 0 ) {
			return [];
		}

		const now = Date.now();
		const validCampaigns = campaigns.filter(
			( campaign ) => campaign.expiry > now
		);
		if ( validCampaigns.length !== campaigns.length ) {
			localStorage.set( storageKey, JSON.stringify( validCampaigns ) );
		}

		return validCampaigns.map( ( campaign ) => campaign.campaign );
	}, [ storageKey ] );

	/**
	 * Inserts or updates a campaign entry in the local cache with a specified expiry.
	 * If the campaign already exists, its expiry is updated; otherwise, a new entry is added.
	 * The updated campaigns list is then saved to localStorage.
	 *
	 * @param {string} campaignId - The unique identifier of the campaign.
	 */
	function upsertActionedCampaign( campaignId ) {
		const campaigns = getActionedCampaigns( storageKey );
		const campaignIndex = campaigns.findIndex(
			( campaign ) => campaign.campaign === campaignId
		);
		const expiry = Date.now() + ttl;

		if ( campaignIndex !== -1 ) {
			campaigns[ campaignIndex ].expiry = expiry;
		} else {
			campaigns.push( { campaign: campaignId, expiry } );
		}

		localStorage.set( storageKey, JSON.stringify( campaigns ) );
	}

	useEffect( () => {
		setCachedCampaigns( getActionedCampaigns() );
	}, [ getActionedCampaigns ] );

	return {
		campaigns: cachedCampaigns,
		upsertActionedCampaign,
	};
};

export default useActionedCampaignsCache;
