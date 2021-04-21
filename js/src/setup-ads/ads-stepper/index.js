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
import SetupBilling from './setup-billing';
import './index.scss';

const AdsStepper = ( props ) => {
	const { formProps } = props;

	// TODO: call API and check if users have already done the account setup,
	// we can straight away bring them to step 2.
	const [ step, setStep ] = useState( '1' );

	// TOOD: figure out when to allow and not to allow step click.
	// Right now we just allow them to go backward, not forward.
	const handleStepClick = ( value ) => {
		if ( value < step ) {
			setStep( value );
		}
	};

	const handleSetupAccountsContinue = () => {
		setStep( '2' );
	};

	const handleCreateCampaignContinue = () => {
		setStep( '3' );
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
						<CreateCampaign
							formProps={ formProps }
							onContinue={ handleCreateCampaignContinue }
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
