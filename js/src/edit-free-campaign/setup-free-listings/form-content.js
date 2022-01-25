/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import TaxRate from '.~/components/free-listings/configure-product-listings/tax-rate';
import useDisplayTaxRate from '.~/components/free-listings/configure-product-listings/useDisplayTaxRate';
import ShippingRateSection from '.~/components/free-listings/configure-product-listings/shipping-rate-section';
import ShippingTimeSection from '.~/components/free-listings/configure-product-listings/shipping-time-section';
import AppButton from '.~/components/app-button';
import ConditionalSection from '.~/components/conditional-section';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Form to configure free listigns.
 * Copied from {@link .~/setup-mc/setup-stepper/setup-free-listings/form-content.js},
 * without auto-save functionality.
 *
 * @param {Object} props React props.
 * @param {Array<CountryCode>} props.countries List of available countries to be forwarded to ShippingRateSection and ShippingTimeSection.
 * @param {Object} props.formProps Form props forwarded from `Form` component, containing free listings settings.
 * @param {boolean} [props.saving=false] Is the form currently beign saved?
 * @param {string} [props.submitLabel="Complete setup"] Submit button label.
 */
const FormContent = ( {
	countries,
	formProps,
	saving = false,
	submitLabel = __( 'Complete setup', 'google-listings-and-ads' ),
} ) => {
	const { isValidForm, handleSubmit } = formProps;

	const shouldDisplayTaxRate = useDisplayTaxRate( countries );

	const isCompleteSetupDisabled =
		shouldDisplayTaxRate === null || ! isValidForm;

	return (
		<StepContent>
			<ShippingRateSection
				formProps={ formProps }
				countries={ countries }
			/>
			<ShippingTimeSection
				formProps={ formProps }
				countries={ countries }
			/>
			<ConditionalSection show={ shouldDisplayTaxRate }>
				<TaxRate formProps={ formProps } />
			</ConditionalSection>
			<StepContentFooter>
				<AppButton
					isPrimary
					disabled={ isCompleteSetupDisabled }
					loading={ saving }
					onClick={ handleSubmit }
				>
					{ submitLabel }
				</AppButton>
			</StepContentFooter>
		</StepContent>
	);
};

export default FormContent;
