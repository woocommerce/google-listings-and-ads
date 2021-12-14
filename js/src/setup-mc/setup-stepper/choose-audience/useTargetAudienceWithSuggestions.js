/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '.~/data/constants';
import useTargetAudience from '.~/hooks/useTargetAudience';
import useGetCountries from '.~/hooks/useGetCountries';
import useApiFetchEffect from '.~/hooks/useApiFetchEffect';

/**
 * @typedef {Object} TargetAudienceWithSuggestionsResult
 * @property {boolean} loading A boolean indicating the network requests are still in progress.
 * @property {Object} data Previously-saved target audience data, or target audience suggestions data.
 */

/**
 * A hook that calls the following APIs in parallel:
 *
 * - Supported countries API, so that the list of supported countries
 * are stored in the wp-data store and ready to be used by children components,
 * e.g. MerchantCenterSelectControl.
 * - Target audience API, to get the previously-saved target audience data.
 * - Target audience suggestions API, to get the suggested target audience data
 * based on WooCommerce store settings.
 *
 * It returns a result object with the following:
 *
 * - `loading`: a boolean indicating the network requests are still in progress.
 * - `data`: if the target audience data does not have location and countries set
 * (i.e. users are visiting Setup MC Step 2 for the first time),
 * `data` will be the target audience suggestions data;
 * otherwise, `data` will be the previously-saved target audience data.
 *
 * @return {TargetAudienceWithSuggestionsResult} A result object with `loading` and `data`.
 */
const useTargetAudienceWithSuggestions = () => {
	const { hasFinishedResolution: hfrCountries } = useGetCountries();
	const {
		hasFinishedResolution: hfrTargetAudience,
		data: dataTargetAudience,
	} = useTargetAudience();
	const {
		loading: loadingSuggestions,
		data: dataSuggestions,
	} = useApiFetchEffect( {
		path: `${ API_NAMESPACE }/mc/target_audience/suggestions`,
	} );

	const hasNoLocationCountries =
		hfrTargetAudience &&
		dataTargetAudience?.location === null &&
		dataTargetAudience?.countries === null;

	return {
		loading: ! hfrCountries || ! hfrTargetAudience || loadingSuggestions,
		data: hasNoLocationCountries ? dataSuggestions : dataTargetAudience,
	};
};

export default useTargetAudienceWithSuggestions;
