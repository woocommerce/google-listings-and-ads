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
import CreateCampaign from './create-campaign';
import './index.scss';

const AdsStepper = () => {
	// TODO: call API and check if users have already done the account setup,
	// we can straight away bring them to step 2.
	const [ step, setStep ] = useState( '1' );

	const handleStepClick = ( value ) => {
		setStep( value );
	};

	const handleSetupAccountsContinue = () => {
		setStep( '2' );
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
					content: <CreateCampaign />,
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default AdsStepper;
