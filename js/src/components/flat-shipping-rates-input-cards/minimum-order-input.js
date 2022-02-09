/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CountryNames from '.~/components/free-listings/configure-product-listings/country-names';
import AppInputPriceControl from '.~/components/app-input-price-control';

const MinimumOrderInput = ( props ) => {
	const { value, onChange } = props;
	const { countries, threshold, currency } = value;

	const handleBlur = ( event, numberValue ) => {
		if ( numberValue === value.threshold ) {
			return;
		}

		onChange( {
			countries,
			threshold: numberValue,
			currency,
		} );
	};

	// TODO: label with edit button.
	return (
		<AppInputPriceControl
			label={ createInterpolateElement(
				__(
					`Minimum order for <countries />`,
					'google-listings-and-ads'
				),
				{
					countries: <CountryNames countries={ countries } />,
				}
			) }
			suffix={ currency }
			value={ threshold }
			onBlur={ handleBlur }
		/>
	);
};

export default MinimumOrderInput;
