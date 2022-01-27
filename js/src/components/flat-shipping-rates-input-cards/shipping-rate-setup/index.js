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
 * @param {Array<CountryCode>} props.audienceCountries Array of country codes of all audience countries.
 * @param {Array<Object>} props.value Array of shipping rates.
 * @param {Function} props.onChange onChange callback.
 */
const ShippingRateSetup = ( { audienceCountries, value, onChange } ) => {
	const { code: currencyCode } = useStoreCurrency();

	if ( ! audienceCountries ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<ShippingCountriesForm
					currencyCode={ currencyCode }
					audienceCountries={ audienceCountries }
					value={ value }
					onChange={ onChange }
				/>
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingRateSetup;
