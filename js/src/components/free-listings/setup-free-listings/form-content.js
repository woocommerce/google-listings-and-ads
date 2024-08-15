/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import StepContent from '.~/components/stepper/step-content';
import StepContentActions from '.~/components/stepper/step-content-actions';
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
 */
const FormContent = ( {
	submitLabel = __( 'Complete setup', 'google-listings-and-ads' ),
} ) => {
	const { values, isValidForm, handleSubmit, adapter } =
		useAdaptiveFormContext();
	const shouldDisplayTaxRate = useDisplayTaxRate( adapter.audienceCountries );
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
			<StepContentActions>
				<AppButton
					isPrimary
					loading={ adapter.isSubmitting }
					onClick={ handleSubmitClick }
				>
					{ submitLabel }
				</AppButton>
			</StepContentActions>
		</StepContent>
	);
};

export default FormContent;
