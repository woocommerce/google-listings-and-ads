/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';

/**
 * Renders the first country name of a program, followed by the count of the remaining ones.
 *
 * @param {Object} props React props.
 * @param {Array<string>} props.countryCodes Country codes of the program.
 */
export default function CountryColumn( { countryCodes } ) {
	const countryNameMap = useCountryKeyNameMap();
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
