/**
 * Internal dependencies
 */
import useStoreCountry from '.~/hooks/useStoreCountry';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Returns a boolean to indicate whether the Tax Rate section should be displayed for given audience countries.
 *
 * The Tax Rate section should be displayed when the store's country code or one of the target audience country codes is `'US'`.
 *
 * @param {Array<CountryCode>} [audienceCountries] If no audience countries are given, check store's one.
 * @return {boolean | null} `true` if the Tax Rate section should be displayed, `false` if shouldn't, `null` we cannot tell - we still wait for store or audience to be determined.
 */
const useDisplayTaxRate = ( audienceCountries = null ) => {
	const { code: storeCountry } = useStoreCountry();

	// If any country is US return `true`.
	if ( storeCountry === 'US' ) {
		return true;
	}
	if ( audienceCountries && audienceCountries.includes( 'US' ) ) {
		return true;
	}
	// If we cannot tell yet, return `null`.
	if ( storeCountry === null || audienceCountries === null ) {
		return null;
	}
	// Store's and audience countries are available and were checked, none contains `US`.
	return false;
};

export default useDisplayTaxRate;
