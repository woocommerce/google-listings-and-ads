/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import More from './more';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Accepts an array of country codes and renders a comma-separated string of country names.
 *
 * If the array's length is same as `total`, it will render a string `'all countries'`.
 *
 * If the country codes is more than `firstN`, it will render the `firstN` country names, and then follow with `'+ <remainingCount> more'`.
 *
 * Usage:
 *
 * ```
 * <CountryNames countries={ ['AU', 'US' ] } total={ 10 }/>
 * ```
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countries Array of all available country codes.
 * @param {number} props.total Number of all countries.
 * @param {number} [props.firstN=5] Number of country names to be shown before displaying "+ <remainingCount> more".
 */
const CountryNames = ( { countries, firstN = 5, total } ) => {
	const keyNameMap = useCountryKeyNameMap();

	if ( countries.length === total ) {
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
