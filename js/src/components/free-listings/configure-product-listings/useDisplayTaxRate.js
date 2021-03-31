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

	if ( ! data ) {
		return false;
	}

	return data.includes( 'US' );
};

export default useDisplayTaxRate;
