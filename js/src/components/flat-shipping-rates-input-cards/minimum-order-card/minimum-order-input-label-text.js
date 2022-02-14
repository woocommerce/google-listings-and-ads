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

const MinimumOrderInputLabelText = ( props ) => {
	const { countries } = props;
	const keyNameMap = useCountryKeyNameMap();

	const firstCountryNames = countries
		.slice( 0, firstN )
		.map( ( c ) => keyNameMap[ c ] );

	const string =
		countries.length > firstCountryNames.length
			? __(
					`Minimum order for <countries /> + <count /> more`,
					'google-listings-and-ads'
			  )
			: __(
					`Minimum order for <countries />`,
					'google-listings-and-ads'
			  );

	return (
		<div>
			{ createInterpolateElement( string, {
				countries: <strong>{ firstCountryNames.join( ', ' ) }</strong>,
				count: <>{ countries.length - firstCountryNames.length }</>,
			} ) }
		</div>
	);
};

export default MinimumOrderInputLabelText;
