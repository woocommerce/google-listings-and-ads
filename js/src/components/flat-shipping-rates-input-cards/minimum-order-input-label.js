/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';

const firstN = 5;

const MinimumOrderInputLabel = ( props ) => {
	const { countries } = props;
	const keyNameMap = useCountryKeyNameMap();

	const firstCountryNames = countries
		.slice( 0, firstN )
		.map( ( c ) => keyNameMap[ c ] );

	if ( countries.length > firstCountryNames.length ) {
		return createInterpolateElement(
			__(
				`Minimum order for <countries /> + <count /> more`,
				'google-listings-and-ads'
			),
			{
				countries: <strong>{ firstCountryNames.join( ', ' ) }</strong>,
				count: <>{ countries.length - firstCountryNames.length }</>,
			}
		);
	}

	return createInterpolateElement(
		__( `Minimum order for <countries />`, 'google-listings-and-ads' ),
		{
			countries: <strong>{ firstCountryNames.join( ', ' ) }</strong>,
		}
	);
};

export default MinimumOrderInputLabel;
