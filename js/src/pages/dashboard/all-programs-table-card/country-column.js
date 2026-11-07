/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Renders the first country name of a program, followed by the count of the remaining ones.
 *
 * @param {Object} props React props.
 * @param {Array<string>} props.countryCodes Country codes of the program.
 * @param {Object<string, string>} props.countryNameMap Map of country codes to country names, from `useCountryKeyNameMap`.
 */
export default function CountryColumn( { countryCodes, countryNameMap } ) {
	const [ first ] = countryCodes;
	return (
		<span>
			{ countryNameMap[ first ] }
			{ countryCodes.length >= 2 &&
				sprintf(
					// translators: %d: number of countries, with minimum value of 1.
					__( ' + %d more', 'google-listings-and-ads' ), // eslint-disable-line @wordpress/i18n-no-flanking-whitespace
					countryCodes.length - 1
				) }
		</span>
	);
}
