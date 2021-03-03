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
import TaxRate from '.~/components/edit-program/free-listings/setup-free-listings/tax-rate';
import PreLaunchChecklist from './pre-launch-checklist';
import isPreLaunchChecklistComplete from './isPreLaunchChecklistComplete';
import useAutoSaveSettingsEffect from './useAutoSaveSettingsEffect';
import useDisplayTaxRate from '.~/components/edit-program/free-listings/setup-free-listings/useDisplayTaxRate';

const FormContent = ( props ) => {
	const { formProps } = props;
	const { values, errors, handleSubmit } = formProps;
	const displayTaxRate = useDisplayTaxRate();

	useAutoSaveSettingsEffect( values );

	const isCompleteSetupDisabled =
		Object.keys( errors ).length >= 1 ||
		! isPreLaunchChecklistComplete( values );

	return (
		<StepContent>
			<ShippingRate formProps={ formProps } />
			<ShippingTime formProps={ formProps } />
			{ displayTaxRate && <TaxRate formProps={ formProps } /> }
			<PreLaunchChecklist formProps={ formProps } />
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
