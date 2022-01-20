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
import ShippingRateSection from './shipping-rate-section';
import ShippingTimeSection from './shipping-time-section';

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
	const { data: audienceCountries } = useTargetAudienceFinalCountryCodes();
	const shouldDisplayTaxRate = useDisplayTaxRate( audienceCountries );

	useAutoSaveSettingsEffect( values );

	return (
		<StepContent>
			<ShippingRateSection formProps={ formProps } />
			<ShippingTimeSection formProps={ formProps } />
			<ConditionalSection show={ shouldDisplayTaxRate }>
				<TaxRate formProps={ formProps } />
			</ConditionalSection>
			<StepContentFooter>{ submitButton }</StepContentFooter>
		</StepContent>
	);
};

export default FormContent;
