/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import More from './more';

/**
 * Accepts an array of country codes and renders a comma-separated string of country names.
 *
 * If the array of country codes is the same as target audience countries, it will render a string `'all countries'`.
 *
 * If the country codes is more than `firstN`, it will render the `firstN` country names, and then follow with `'+ <remainingCount> more'`.
 *
 * Usage:
 *
 * ```
 * <CountryNames countries={ ['AU', 'US' ] } />
 * ```
 *
 * @param {Object} props
 * @param {Array<string>} props.countries Array of country codes.
 * @param {number} props.firstN Number of country names to be shown before displaying "+ <remainingCount> more".
 */
const CountryNames = ( props ) => {
	const { countries, firstN = 5 } = props;
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();
	const keyNameMap = useCountryKeyNameMap();

	if ( selectedCountryCodes.length === countries.length ) {
		return (
			<strong>
				{ __( `all countries`, 'google-listings-and-ads' ) }
			</strong>
		);
	}

	const firstCountryNames = countries
		.slice( 0, firstN )
		.map( ( c ) => keyNameMap[ c ] );

	return (
		<span>
			<strong>{ firstCountryNames.join( ', ' ) }</strong>
			<More count={ countries.length - firstCountryNames.length } />
		</span>
	);
};

export default CountryNames;
