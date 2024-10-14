/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';

const selectorName = 'getAdsCampaigns';

/**
 * @typedef {import('.~/data/actions').Campaign} Campaign
 *
 * @typedef {Object} AdsCampaignsPayload
 * @property {Array<Campaign>|null} data Current campaigns obtained from merchant's Google Ads account if connected. It will be `null` before load finished.
 * @property {boolean} loading Whether the `data` is loading. It's equal to `isResolving` state of wp-data selector.
 * @property {boolean} loaded Whether the `data` is finished loading. It's equal to `hasFinishedResolution` state of wp-data selector.
 */

/**
 * A hook that calls `getAdsCampaigns` selector to load current campaigns
 * from merchant's Google Ads account if connected.
 *
 * @param {{exclude_removed: true|false}} query Query to be forwarded to the selector.
 * @return {AdsCampaignsPayload} The data and its state.
 */
const useAdsCampaigns = ( ...query ) => {
	const queryRefValue = useIsEqualRefValue( query );
	const { hasGoogleAdsConnection, hasFinishedResolution } =
		useGoogleAdsAccount();

	return useSelect(
		( select ) => {
			if ( hasFinishedResolution && ! hasGoogleAdsConnection ) {
				return {
					loading: false,
					loaded: true,
					data: [],
				};
			}

			const selector = select( STORE_KEY );
			const data = selector[ selectorName ]( ...queryRefValue );
			const loading = selector.isResolving( selectorName, queryRefValue );

			const loaded = selector.hasFinishedResolution(
				selectorName,
				queryRefValue
			);

			return {
				loading,
				loaded,
				data,
			};
		},
		[ queryRefValue, hasGoogleAdsConnection, hasFinishedResolution ]
	);
};

export default useAdsCampaigns;
