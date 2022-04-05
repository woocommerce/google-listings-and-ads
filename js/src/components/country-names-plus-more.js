/**
 * External dependencies
 */
import { sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';

const defaultFirstN = 5;

const CountryNamesPlusMore = ( props ) => {
	const {
		countries,
		firstN = defaultFirstN,
		textWithMore,
		textWithoutMore,
	} = props;
	const keyNameMap = useCountryKeyNameMap();

	const firstCountryNames = countries
		.slice( 0, firstN )
		.map( ( c ) => keyNameMap[ c ] );

	const string =
		countries.length > firstCountryNames.length
			? textWithMore
			: textWithoutMore;

	return createInterpolateElement(
		sprintf(
			string,
			firstCountryNames.join( ', ' ),
			countries.length - firstCountryNames.length
		),
		{
			strong: <strong />,
		}
	);
};

export default CountryNamesPlusMore;
