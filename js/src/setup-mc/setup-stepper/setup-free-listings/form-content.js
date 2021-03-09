/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import ShippingRate from './shipping-rate';
import ShippingTime from './shipping-time';
import TaxRate from '.~/components/free-listings/configure-product-listings/tax-rate';
import PreLaunchChecklist from './pre-launch-checklist';
import useAutoSaveSettingsEffect from './useAutoSaveSettingsEffect';
import useDisplayTaxRate from '.~/components/free-listings/configure-product-listings/useDisplayTaxRate';

const FormContent = ( props ) => {
	const { formProps, submitButton } = props;
	const { values } = formProps;
	const displayTaxRate = useDisplayTaxRate();

	useAutoSaveSettingsEffect( values );

	return (
		<StepContent>
			<ShippingRate formProps={ formProps } />
			<ShippingTime formProps={ formProps } />
			{ displayTaxRate && <TaxRate formProps={ formProps } /> }
			<PreLaunchChecklist formProps={ formProps } />
			<StepContentFooter>{ submitButton }</StepContentFooter>
		</StepContent>
	);
};

export default FormContent;
