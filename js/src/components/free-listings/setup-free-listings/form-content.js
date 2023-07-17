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
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Form to configure free listigns.
 *
 * @param {Object} props React props.
 * @param {Array<CountryCode>} props.countries List of available countries to be forwarded to ShippingRateSection and ShippingTimeSection.
 * @param {string} [props.submitLabel="Complete setup"] Submit button label.
 */
const FormContent = ( {
	countries,
	submitLabel = __( 'Complete setup', 'google-listings-and-ads' ),
} ) => {
	const {
		values,
		isValidForm,
		handleSubmit,
		adapter,
	} = useAdaptiveFormContext();
	const shouldDisplayTaxRate = useDisplayTaxRate( countries );
	const shouldDisplayShippingTime = values.shipping_time === 'flat';
	const isCompleteSetupDisabled =
		shouldDisplayTaxRate === null || ! isValidForm;

	return (
		<StepContent>
			<ChooseAudienceSection />
			<ShippingRateSection audienceCountries={ countries } />
			{ shouldDisplayShippingTime && (
				<ShippingTimeSection countries={ countries } />
			) }
			<ConditionalSection show={ shouldDisplayTaxRate }>
				<TaxRate />
			</ConditionalSection>
			<StepContentFooter>
				<AppButton
					isPrimary
					disabled={ isCompleteSetupDisabled }
					loading={ adapter.isSubmitting }
					onClick={ handleSubmit }
				>
					{ submitLabel }
				</AppButton>
			</StepContentFooter>
		</StepContent>
	);
};

export default FormContent;
