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
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import SetupBilling from './setup-billing';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import ContinueButton from '.~/components/paid-ads/ads-campaign/continue-button';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
	FILTER_ONBOARDING,
	CONTEXT_ADS_ONBOARDING,
} from '.~/utils/tracks';

/**
 * @param {Object} props React props
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @fires gla_setup_ads with `{ triggered_by: 'step1-continue-button' | 'step2-continue-button' , action: 'go-to-step2' | 'go-to-step3' }`.
 * @fires gla_setup_ads with `{ triggered_by: 'stepper-step1-button' | 'stepper-step2-button', action: 'go-to-step1' | 'go-to-step2' }`.
 */
const AdsStepper = ( { formProps } ) => {
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

	const handleCreateCampaignContinue = () => {
		continueStep( '3' );
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
					content: (
						<AdsCampaign
							headerTitle={ __(
								'Create your paid campaign',
								'google-listings-and-ads'
							) }
							trackingContext="setup-ads"
							continueButton={ ( formContext ) => (
								<ContinueButton
									formProps={ formContext }
									onClick={ handleCreateCampaignContinue }
								/>
							) }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: '3',
					label: __( 'Set up billing', 'google-listings-and-ads' ),
					content: <SetupBilling formProps={ formProps } />,
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default AdsStepper;
