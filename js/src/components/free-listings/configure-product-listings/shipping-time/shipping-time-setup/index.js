/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import AppSpinner from '.~/components/app-spinner';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import ShippingCountriesForm from './countries-form';
import './index.scss';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Form control to edit shipping rate settings.
 *
 * @param {Object} props React props.
 * @param {Array<CountryCode>} props.selectedCountryCodes Array of country codes of all audience countries.
 */
const ShippingTimeSetup = ( { selectedCountryCodes } ) => {
	const { getInputProps } = useAdaptiveFormContext();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-shipping-time-setup">
			<VerticalGapLayout>
				<ShippingCountriesForm
					{ ...getInputProps( 'shipping_country_times' ) }
					selectedCountryCodes={ selectedCountryCodes }
				/>
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingTimeSetup;
