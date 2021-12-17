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
				`${ API_NAMESPACE }/mc/shipping/rates/suggestions/batch`,
				{ country_codes: dataFinalCountryCodes }
			),
		}
	);

	/**
	 * Returned `data` is consistent with shipping rates structure in wp-data.
	 */
	return {
		loading: loadingFinalCountryCodes || loadingSuggestions,
		data: dataSuggestions?.success.map( ( el ) => ( {
			countryCode: el.country_code,
			currency: el.currency,
			rate: el.rate,
		} ) ),
	};
};

export default useShippingRatesSuggestions;
