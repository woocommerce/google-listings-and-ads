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
import ShippingRate from './shipping-rate';
import ShippingTime from './shipping-time';
import TaxRate from './tax-rate';
import useDisplayTaxRate from './useDisplayTaxRate';

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
