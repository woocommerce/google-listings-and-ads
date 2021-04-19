/**
 * Internal dependencies
 */
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

/**
 * Returns a boolean to indicate whether the Tax Rate section should be displayed.
 *
 * The Tax Rate section should be displayed when the country code `'US'` is one of the target audience countries.
 */
const useDisplayTaxRate = () => {
	const { data } = useTargetAudienceFinalCountryCodes();

	return shouldDisplayTaxRate( data );
};

export default useDisplayTaxRate;

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Checks whether the Tax Rate section should be displayed, for given audience.
 * The Tax Rate section should be displayed when there is `'US'` country code in the target audience countries list.
 *
 * @param {Array<CountryCode>} countries
 * @return {boolean} `true` if the Tax Rate section should be displayed, `false` otherwise.
 */
export function shouldDisplayTaxRate( countries ) {
	if ( ! countries ) {
		return false;
	}

	return countries.includes( 'US' );
}
