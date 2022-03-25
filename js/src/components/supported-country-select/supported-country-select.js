/**
 * Internal dependencies
 */
import AppCountrySelect from '.~/components/app-country-select';
import useMCCountries from '.~/hooks/useMCCountries';

const SupportedCountrySelect = ( props ) => {
	const { data } = useMCCountries();

	const options = data ? Object.keys( data ) : [];

	return <AppCountrySelect options={ options } { ...props } />;
};

export default SupportedCountrySelect;
