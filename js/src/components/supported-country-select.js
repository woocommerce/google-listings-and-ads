/**
 * Internal dependencies
 */
import AppCountrySelect from '.~/components/app-country-select';
import useGetCountries from '.~/hooks/useGetCountries';

const SupportedCountrySelect = ( props ) => {
	const { data } = useGetCountries();

	const options = data ? Object.keys( data ) : [];

	return (
		<AppCountrySelect hideBeforeSearch options={ options } { ...props } />
	);
};

export default SupportedCountrySelect;
