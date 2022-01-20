/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useApiFetchEffect from '.~/hooks/useApiFetchEffect';
import { API_NAMESPACE } from '.~/data/constants';

/**
 * @typedef {Object} ShippingRatesSuggestionsResult
 * @property {boolean} loading Whether loading is in progress.
 * @property {Array<import('.~/data/actions').ShippingRate>?} data Shipping rates suggestions.
 */

/**
 * Get the shipping rates suggestions.
 *
 * This depends on the `useTargetAudienceFinalCountryCodes` hook,
 * i.e. the target audience countres specified in Setup MC Step 2.
 *
 * @return {ShippingRatesSuggestionsResult} Result object with `loading` and `data`.
 */
const useShippingRatesSuggestions = () => {
	const {
		loading: loadingFinalCountryCodes,
		data: dataFinalCountryCodes,
	} = useTargetAudienceFinalCountryCodes();

	/**
	 * The API will only be called when `dataFinalCountryCodes` is truthy.
	 */
	const {
		loading: loadingSuggestions,
		data: dataSuggestions,
	} = useApiFetchEffect(
		dataFinalCountryCodes && {
			path: addQueryArgs(
				`${ API_NAMESPACE }/mc/shipping/rates/suggestions`,
				{ country_codes: dataFinalCountryCodes }
			),
		}
	);

	return {
		loading: loadingFinalCountryCodes || loadingSuggestions,
		data: dataSuggestions,
	};
};

export default useShippingRatesSuggestions;
