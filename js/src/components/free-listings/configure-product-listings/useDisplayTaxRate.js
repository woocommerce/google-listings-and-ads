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
 * @param {Array<CountryCode>} [audienceCountries=[]] If no audience countries are given, check only store's one.
 */
const useDisplayTaxRate = ( audienceCountries = [] ) => {
	const { code: storeCountry } = useStoreCountry();

	return shouldDisplayTaxRate( [ ...audienceCountries, storeCountry ] );
};

export default useDisplayTaxRate;

/**
 * Checks whether the Tax Rate section should be displayed,
 * for a given list of involved countries (audience and store's).
 * The Tax Rate section should be displayed when there is `'US'` country code in the countries list.
 *
 * @param {Array<CountryCode>} countries
 * @return {boolean} `true` if the Tax Rate section should be displayed, `false` otherwise.
 */
export function shouldDisplayTaxRate( countries ) {
	return countries.includes( 'US' );
}
