/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import TaxRate from '.~/components/free-listings/configure-product-listings/tax-rate';
import useDisplayTaxRate from '.~/components/free-listings/configure-product-listings/useDisplayTaxRate';
import CombinedShipping from '.~/components/free-listings/configure-product-listings/combined-shipping';

/**
 * Form to configure free listigns.
 * Copied from {@link .~/setup-mc/setup-stepper/setup-free-listings/form-content.js},
 * without auto-save functionality.
 *
 * @param {Object} props
 */
const FormContent = ( props ) => {
	const { formProps } = props;
	const { errors, handleSubmit } = formProps;
	const displayTaxRate = useDisplayTaxRate();

	const isCompleteSetupDisabled = Object.keys( errors ).length >= 1;

	return (
		<StepContent>
			<CombinedShipping formProps={ formProps } />
			{ displayTaxRate && <TaxRate formProps={ formProps } /> }
			<StepContentFooter>
				<Button
					isPrimary
					disabled={ isCompleteSetupDisabled }
					onClick={ handleSubmit }
				>
					{ __( 'Complete setup', 'google-listings-and-ads' ) }
				</Button>
			</StepContentFooter>
		</StepContent>
	);
};

export default FormContent;
