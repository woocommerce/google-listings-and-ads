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

const AdsStepper = ( props ) => {
	const { formProps } = props;
	const [ step, setStep ] = useState( '1' );

	// Allow the users to go backward only, not forward.
	// Users can only go forward by clicking on the Continue button.
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
