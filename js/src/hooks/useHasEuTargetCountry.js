/**
 * Internal dependencies
 */
import useTargetAudience from '~/hooks/useTargetAudience';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

/**
 * Checks whether any country in the target audience is in the EU.
 *
 * @return {{ hasFinishedResolution: boolean, hasEuTargetCountry: boolean }} Whether the data has loaded, and whether any target country is in the EU. `hasEuTargetCountry` is `false` until the data has loaded.
 */
const useHasEuTargetCountry = () => {
	const {
		data: { continents },
		hasFinishedResolution: hasResolvedCountriesAndContinents,
	} = useAppSelectDispatch( 'getMCCountriesAndContinents' );
	const { data, hasFinishedResolution: hasResolvedTargetAudience } =
		useTargetAudience();

	const hasFinishedResolution =
		hasResolvedCountriesAndContinents && hasResolvedTargetAudience;

	if ( ! hasFinishedResolution ) {
		return { hasFinishedResolution, hasEuTargetCountry: false };
	}

	const targetCountries = Array.isArray( data?.countries )
		? data.countries
		: [];
	const euCountries = continents?.EU?.countries || [];
	const hasEuTargetCountry = targetCountries.some( ( countryCode ) =>
		euCountries.includes( countryCode )
	);

	return { hasFinishedResolution, hasEuTargetCountry };
};

export default useHasEuTargetCountry;
