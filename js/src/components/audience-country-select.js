/**
 * Internal dependencies
 */
import SupportedCountrySelect from '.~/components/supported-country-select';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

/**
 * Returns a SupportedCountrySelect component with list of countries grouped by continents.
 * And SupportedCountrySelect will be rendered via TreeSelectControl component.
 *
 * This component is for selecting countries under the merchant selected targeting audiences.
 *
 * @param {Object} props React props to be forwarded to SupportedCountrySelect.
 */
const AudienceCountrySelect = ( props ) => {
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! countryCodes ) {
		return <AppSpinner />;
	}

	return (
		<SupportedCountrySelect { ...props } countryCodes={ countryCodes } />
	);
};

export default AudienceCountrySelect;
