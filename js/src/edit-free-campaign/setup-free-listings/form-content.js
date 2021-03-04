/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/edit-program/step-content';
import StepContentFooter from '.~/components/edit-program/step-content-footer';
import TaxRate from '.~/components/edit-program/free-listings/setup-free-listings/tax-rate';
import useDisplayTaxRate from '.~/components/edit-program/free-listings/setup-free-listings/useDisplayTaxRate';
import ShippingRate from '.~/setup-mc/setup-stepper/setup-free-listings/shipping-rate';
import ShippingTime from '.~/setup-mc/setup-stepper/setup-free-listings/shipping-time';

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
			<ShippingRate formProps={ formProps } />
			<ShippingTime formProps={ formProps } />
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
