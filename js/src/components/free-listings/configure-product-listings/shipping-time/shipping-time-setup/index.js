/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import AppSpinner from '.~/components/app-spinner';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import ShippingCountriesForm from './countries-form';
import './index.scss';

/**
 * Form control to edit shipping rate settings.
 */
const ShippingTimeSetup = () => {
	const { getInputProps, adapter } = useAdaptiveFormContext();

	if ( ! adapter.audienceCountries ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-shipping-time-setup">
			<VerticalGapLayout>
				<ShippingCountriesForm
					{ ...getInputProps( 'shipping_country_times' ) }
					audienceCountries={ adapter.audienceCountries }
				/>
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingTimeSetup;
