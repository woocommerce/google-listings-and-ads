/**
 * Internal dependencies
 */
import AppCountrySelect from '.~/components/app-country-select';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

const AudienceCountrySelect = ( props ) => {
	const { data } = useTargetAudienceFinalCountryCodes();

	if ( ! data ) {
		return <AppSpinner />;
	}

	return <AppCountrySelect options={ data } { ...props } />;
};

export default AudienceCountrySelect;
