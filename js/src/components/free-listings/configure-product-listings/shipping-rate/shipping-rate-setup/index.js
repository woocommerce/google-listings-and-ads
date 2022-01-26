/**
 * Internal dependencies
 */
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import AppSpinner from '.~/components/app-spinner';
import ShippingCountriesForm from './countries-form';
import './index.scss';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Form control to edit shipping rate settings.
 *
 * @param {Object} props React props.
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {Array<CountryCode>} props.selectedCountryCodes Array of country codes of all audience countries.
 */
const ShippingRateSetup = ( { formProps, selectedCountryCodes } ) => {
	const { getInputProps } = formProps;
	const { code: currencyCode } = useStoreCurrency();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<ShippingCountriesForm
					{ ...getInputProps( 'shipping_country_rates' ) }
					currencyCode={ currencyCode }
					audienceCountries={ selectedCountryCodes }
				/>
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingRateSetup;
