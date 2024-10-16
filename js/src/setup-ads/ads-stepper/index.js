/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SetupAccounts from './setup-accounts';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
	FILTER_ONBOARDING,
	CONTEXT_ADS_ONBOARDING,
} from '.~/utils/tracks';
import SetupPaidAds from './setup-paid-ads';

/**
 * @param {Object} props React props
 * @param {boolean} props.isSubmitting When the form in the parent component, i.e SetupAdsForm, is currently being submitted via the useAdsSetupCompleteCallback hook.
 * @fires gla_setup_ads with `{ triggered_by: 'step1-continue-button', action: 'go-to-step2' }`.
 * @fires gla_setup_ads with `{ triggered_by: 'stepper-step1-button', action: 'go-to-step1'}`.
 */
const AdsStepper = ( { isSubmitting } ) => {
	const [ step, setStep ] = useState( '1' );

	useEventPropertiesFilter( FILTER_ONBOARDING, {
		context: CONTEXT_ADS_ONBOARDING,
		step,
	} );

	// Allow the users to go backward only, not forward.
	// Users can only go forward by clicking on the Continue button.
	const handleStepClick = ( value ) => {
		if ( value < step ) {
			recordStepperChangeEvent( 'gla_setup_ads', value );
			setStep( value );
		}
	};

	/**
	 * Handles "onContinue" callback to set the current step and record event tracking.
	 *
	 * @param {string} to The next step to go to.
	 */
	const continueStep = ( to ) => {
		const from = step;

		recordStepContinueEvent( 'gla_setup_ads', from, to );
		setStep( to );
	};

	const handleSetupAccountsContinue = () => {
		continueStep( '2' );
	};

	return (
		// This Stepper with this class name
		// should be refactored into separate shared component.
		// It is also used in the Setup MC flow.
		<Stepper
			className="gla-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: '1',
					label: __(
						'Set up your accounts',
						'google-listings-and-ads'
					),
					content: (
						<SetupAccounts
							onContinue={ handleSetupAccountsContinue }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: '2',
					label: __(
						'Create your paid campaign',
						'google-listings-and-ads'
					),
					content: <SetupPaidAds isSubmitting={ isSubmitting } />,
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default AdsStepper;
