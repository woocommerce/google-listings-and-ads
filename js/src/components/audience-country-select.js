/**
 * Internal dependencies
 */
import SupportedCountrySelect from '.~/components/supported-country-select';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Returns a SupportedCountrySelect component with list of countries grouped by continents.
 * And SupportedCountrySelect will be rendered via TreeSelectControl component.
 *
 * This component is for selecting countries under the merchant selected targeting audiences.
 *
 * @param {Object} props React props.
 * @param {Array<CountryCode>} [props.additionalCountryCodes] Additional countries that are not in the target audience countries and need to be selectable.
 * @param {Object} props.restProps Props to be forwarded to SupportedCountrySelect.
 */
const AudienceCountrySelect = ( { additionalCountryCodes, ...restProps } ) => {
	let { data: countryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! countryCodes ) {
		return <AppSpinner />;
	}

	if ( additionalCountryCodes ) {
		countryCodes = Array.from(
			new Set( countryCodes.concat( additionalCountryCodes ) )
		);
	}

	return (
		<SupportedCountrySelect
			{ ...restProps }
			countryCodes={ countryCodes }
		/>
	);
};

export default AudienceCountrySelect;
