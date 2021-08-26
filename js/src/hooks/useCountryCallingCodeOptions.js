/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { getCountries, getCountryCallingCode } from 'libphonenumber-js';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from './useCountryKeyNameMap';

const toOption = ( country, countryCallingCode, countryName ) => ( {
	key: country,
	keywords: [ countryName, countryCallingCode, country ],
	label: `${ countryName } (+${ countryCallingCode })`,
} );

export default function useCountryCallingCodeOptions() {
	const countryNameDict = useCountryKeyNameMap();

	return useMemo( () => {
		return getCountries().reduce( ( acc, country ) => {
			const countryName = countryNameDict[ country ];
			if ( countryName ) {
				const countryCallingCode = getCountryCallingCode( country );
				acc.push(
					toOption( country, countryCallingCode, countryName )
				);
			}
			return acc;
		}, [] );
	}, [ countryNameDict ] );
}
