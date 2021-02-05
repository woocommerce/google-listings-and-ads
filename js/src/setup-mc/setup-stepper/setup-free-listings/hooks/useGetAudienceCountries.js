/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import useAudienceSelectedCountryCodes from '../../choose-audience/useAudienceSelectedCountryCodes';

// from Step 2 Choose Your Audience.
// TODO: consider not to use this and remove this, because this is too coupled with the label logic.
const useGetAudienceCountries = () => {
	const [ value ] = useAudienceSelectedCountryCodes();
	const keyNameMap = useCountryKeyNameMap();

	const result = value.map( ( el ) => {
		return {
			key: el,
			label: keyNameMap[ el ],
		};
	} );

	return result;
};

export default useGetAudienceCountries;
