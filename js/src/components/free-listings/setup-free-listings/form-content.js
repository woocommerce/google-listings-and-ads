/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import StepContent from '.~/components/stepper/step-content';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import TaxRate from '.~/components/free-listings/configure-product-listings/tax-rate';
import useDisplayTaxRate from '.~/components/free-listings/configure-product-listings/useDisplayTaxRate';
import ChooseAudienceSection from '.~/components/free-listings/choose-audience-section';
import ShippingRateSection from '.~/components/shipping-rate-section';
import ShippingTimeSection from '.~/components/free-listings/configure-product-listings/shipping-time-section';
import AppButton from '.~/components/app-button';
import ConditionalSection from '.~/components/conditional-section';

/**
 * Form to configure free listigns.
 *
 * @param {Object} props React props.
 * @param {string} [props.submitLabel="Complete setup"] Submit button label.
 * @param {boolean} [props.hideTaxRates=false] Whether to hide tax rate section.
 */
const FormContent = ( {
	submitLabel = __( 'Complete setup', 'google-listings-and-ads' ),
	hideTaxRates,
} ) => {
	const { values, isValidForm, handleSubmit, adapter } =
		useAdaptiveFormContext();
	const displayTaxRate = useDisplayTaxRate( adapter.audienceCountries );
	const shouldDisplayTaxRate = ! hideTaxRates && displayTaxRate;
	const shouldDisplayShippingTime = values.shipping_time === 'flat';

	const handleSubmitClick = ( event ) => {
		if ( shouldDisplayTaxRate !== null && isValidForm ) {
			return handleSubmit( event );
		}

		adapter.showValidation();
	};

	return (
		<StepContent>
			<ChooseAudienceSection />
			<ShippingRateSection />
			{ shouldDisplayShippingTime && <ShippingTimeSection /> }
			<ConditionalSection show={ shouldDisplayTaxRate }>
				<TaxRate />
			</ConditionalSection>
			<StepContentFooter>
				<AppButton
					isPrimary
					loading={ adapter.isSubmitting }
					onClick={ handleSubmitClick }
				>
					{ submitLabel }
				</AppButton>
			</StepContentFooter>
		</StepContent>
	);
};

export default FormContent;
