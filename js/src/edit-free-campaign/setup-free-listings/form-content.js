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
import CombinedShipping from '.~/components/free-listings/configure-product-listings/combined-shipping';
import AppButton from '.~/components/app-button';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Form to configure free listigns.
 * Copied from {@link .~/setup-mc/setup-stepper/setup-free-listings/form-content.js},
 * without auto-save functionality.
 *
 * @param {Object} props React props.
 * @param {Array<CountryCode>} props.countries List of available countries to be forwarded to CombinedShipping.
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
	const { errors, handleSubmit } = formProps;

	const displayTaxRate = useDisplayTaxRate( countries );

	const isCompleteSetupDisabled = Object.keys( errors ).length >= 1;

	return (
		<StepContent>
			<CombinedShipping formProps={ formProps } countries={ countries } />
			{ displayTaxRate && <TaxRate formProps={ formProps } /> }
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
