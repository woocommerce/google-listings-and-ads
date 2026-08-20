/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';

const selectorName = 'getAdsSettings';

const useAdsSettings = () => {
	const { hasGoogleAdsConnection, hasFinishedResolution } =
		useGoogleAdsAccount();

	return useSelect(
		( select ) => {
			if ( ! hasGoogleAdsConnection ) {
				return {
					adsSettings: null,
					hasFinishedResolution,
				};
			}

			const selector = select( STORE_KEY );

			return {
				adsSettings: selector[ selectorName ](),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ hasGoogleAdsConnection, hasFinishedResolution ]
	);
};

export default useAdsSettings;
