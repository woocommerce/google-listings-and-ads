/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { recordSetupMCEvent } from '../../utils/recordEvent';
import SetupAccounts from './setup-accounts';
import SetupFreeListings from './setup-free-listings';
import ChooseAudience from './choose-audience';
import './index.scss';

const SetupStepper = () => {
	// TODO: get the user's current step from API backend.
	const [ step, setStep ] = useState( 3 );

	const handleSetupAccountsContinue = () => {
		recordSetupMCEvent( 'step1_continue' );
		setStep( 2 );
	};

	const handleChooseAudienceContinue = () => {
		recordSetupMCEvent( 'step2_continue' );
		setStep( 3 );
	};

	return (
		<Stepper
			className="gla-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: 1,
					label: __(
						'Set up your accounts',
						'google-listings-and-ads'
					),
					content: (
						<SetupAccounts
							onContinue={ handleSetupAccountsContinue }
						/>
					),
				},
				{
					key: 2,
					label: __(
						'Choose your audience',
						'google-listings-and-ads'
					),
					content: (
						<ChooseAudience
							onContinue={ handleChooseAudienceContinue }
						/>
					),
				},
				{
					key: 3,
					label: __(
						'Configure your free listings',
						'google-listings-and-ads'
					),
					content: <SetupFreeListings />,
				},
			] }
		/>
	);
};

export default SetupStepper;
