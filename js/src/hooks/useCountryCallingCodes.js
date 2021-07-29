/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { getCountries, getCountryCallingCode } from 'libphonenumber-js';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from './useCountryKeyNameMap';

const toData = ( country, countryCallingCode, countryName ) => ( {
	country,
	countryCallingCode,
	countryName,
} );

const toOption = ( country, countryCallingCode, countryName ) => ( {
	key: country,
	keywords: [ countryName, countryCallingCode, country ],
	label: `${ countryName } (+${ countryCallingCode })`,
} );

export default function useCountryCallingCodes( as ) {
	const countryNameDict = useCountryKeyNameMap();

	return useMemo( () => {
		return getCountries().reduce( ( acc, country ) => {
			const countryName = countryNameDict[ country ];
			if ( countryName ) {
				const fn = as === 'select-options' ? toOption : toData;
				const countryCallingCode = getCountryCallingCode( country );
				acc.push( fn( country, countryCallingCode, countryName ) );
			}
			return acc;
		}, [] );
	}, [ as, countryNameDict ] );
}
