/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import TaxRate from '.~/components/free-listings/configure-product-listings/tax-rate';
import useAutoSaveSettingsEffect from './useAutoSaveSettingsEffect';
import useDisplayTaxRate from '.~/components/free-listings/configure-product-listings/useDisplayTaxRate';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import ConditionalSection from '.~/components/conditional-section';
import ShippingRateSection from '.~/components/shipping-rate-section';
import ShippingTimeSection from './shipping-time-section';
import useAutoSaveShippingRatesEffect from './useAutoSaveShippingRatesEffect';

/**
 * Form to configure free listings.
 * Auto-saves changes.
 *
 * @see /js/src/edit-free-campaign/setup-free-listings/form-content.js
 * @param {Object} props
 */
const FormContent = ( props ) => {
	const { formProps, submitButton } = props;
	const { values } = formProps;
	const {
		shipping_country_rates: shippingRatesValue,
		...settingsValue
	} = values;
	const { data: audienceCountries } = useTargetAudienceFinalCountryCodes();
	const shouldDisplayTaxRate = useDisplayTaxRate( audienceCountries );
	const shouldDisplayShippingTime = values.shipping_time === 'flat';

	useAutoSaveSettingsEffect( settingsValue );
	useAutoSaveShippingRatesEffect( shippingRatesValue );

	return (
		<StepContent>
			<ShippingRateSection
				formProps={ formProps }
				audienceCountries={ audienceCountries }
			/>
			{ shouldDisplayShippingTime && (
				<ShippingTimeSection formProps={ formProps } />
			) }
			<ConditionalSection show={ shouldDisplayTaxRate }>
				<TaxRate formProps={ formProps } />
			</ConditionalSection>
			<StepContentFooter>{ submitButton }</StepContentFooter>
		</StepContent>
	);
};

export default FormContent;
