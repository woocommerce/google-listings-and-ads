/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';

const firstN = 5;

const ShippingRateInputControlLabelText = ( props ) => {
	const { countries } = props;
	const keyNameMap = useCountryKeyNameMap();

	const firstCountryNames = countries
		.slice( 0, firstN )
		.map( ( c ) => keyNameMap[ c ] );

	const string =
		countries.length > firstCountryNames.length
			? // translators: 1: list of country names separated by comma, up to 5 countries; 2: the remaining count of countries.
			  __(
					`Shipping rate for <strong>%1$s</strong> + %2$d more`,
					'google-listings-and-ads'
			  )
			: // translators: 1: list of country names separated by comma.
			  __(
					`Shipping rate for <strong>%1$s</strong>`,
					'google-listings-and-ads'
			  );

	return (
		<div>
			{ createInterpolateElement(
				sprintf(
					string,
					firstCountryNames.join( ', ' ),
					countries.length - firstCountryNames.length
				),
				{
					strong: <strong />,
				}
			) }
		</div>
	);
};

export default ShippingRateInputControlLabelText;
