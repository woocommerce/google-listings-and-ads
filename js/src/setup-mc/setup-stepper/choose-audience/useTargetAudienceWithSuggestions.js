/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useTargetAudience from '.~/hooks/useTargetAudience';
import useGetCountries from '.~/hooks/useGetCountries';

const useTargetAudienceWithSuggestions = () => {
	const { hasFinishedResolution: hfrCountries } = useGetCountries();
	const {
		hasFinishedResolution: hfrTargetAudience,
		data: dataTargetAudience,
	} = useTargetAudience();
	const [
		fetchSuggestions,
		{
			loading: loadingSuggestions,
			data: dataSuggestions,
			response: responseSuggestions,
		},
	] = useApiFetchCallback( {
		path: `wc/gla/mc/target_audience/suggestions`,
	} );

	const hasNoLocationCountries =
		hfrTargetAudience &&
		dataTargetAudience?.location === null &&
		dataTargetAudience?.countries === null;

	const hasNotFetchedSuggestions =
		! loadingSuggestions && ! responseSuggestions;

	useEffect( () => {
		if ( hasNoLocationCountries && hasNotFetchedSuggestions ) {
			fetchSuggestions();
		}
	}, [ fetchSuggestions, hasNoLocationCountries, hasNotFetchedSuggestions ] );

	if ( hasNoLocationCountries ) {
		return {
			loading:
				! hfrCountries || ! hfrTargetAudience || ! responseSuggestions,
			data: dataSuggestions || dataTargetAudience,
		};
	}

	return {
		loading: ! hfrCountries || ! hfrTargetAudience,
		data: dataTargetAudience,
	};
};

export default useTargetAudienceWithSuggestions;
