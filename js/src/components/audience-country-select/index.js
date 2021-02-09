/**
 * Internal dependencies
 */
import AppCountrySelect from '.~/components/app-country-select';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import AppSpinner from '../app-spinner';

const AudienceCountrySelect = ( props ) => {
	const { data } = useTargetAudienceFinalCountryCodes();

	if ( ! data ) {
		return <AppSpinner />;
	}

	return <AppCountrySelect options={ data } { ...props } />;
};

export default AudienceCountrySelect;
