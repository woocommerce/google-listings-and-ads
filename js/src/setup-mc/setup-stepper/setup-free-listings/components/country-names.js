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
			<More count={ countries.length - firstN } />
		</span>
	);
};

export default CountryNames;
