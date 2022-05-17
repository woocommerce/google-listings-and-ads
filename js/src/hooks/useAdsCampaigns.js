/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';
import { glaData } from '.~/constants';
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
	const argsRefValue = useIsEqualRefValue( query );

	return useSelect(
		( select ) => {
			// TODO: ideally adsSetupComplete should be retrieved from API endpoint
			// and then put into wp-data.
			// With that in place, then we don't need to depend on glaData
			// which requires force reload using window.location.href.
			const { adsSetupComplete } = glaData;

			if ( ! adsSetupComplete ) {
				return {
					loading: false,
					loaded: true,
					data: [],
				};
			}

			const selector = select( STORE_KEY );
			const data = selector[ selectorName ]( ...argsRefValue );
			const loading = selector.isResolving( selectorName, argsRefValue );

			const loaded = selector.hasFinishedResolution(
				selectorName,
				argsRefValue
			);

			return {
				loading,
				loaded,
				data,
			};
		},
		[ argsRefValue ]
	);
};

export default useAdsCampaigns;
