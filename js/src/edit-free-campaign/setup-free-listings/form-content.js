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
import ShippingRate from '.~/components/edit-program/free-listings/setup-free-listings/shipping-rate';
import ShippingTime from '.~/components/edit-program/free-listings/setup-free-listings/shipping-time';
import TaxRate from '.~/components/edit-program/free-listings/setup-free-listings/tax-rate';
import useDisplayTaxRate from '.~/components/edit-program/free-listings/setup-free-listings/useDisplayTaxRate';

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
					{ __( 'Save changes', 'google-listings-and-ads' ) }
				</Button>
			</StepContentFooter>
		</StepContent>
	);
};

export default FormContent;
