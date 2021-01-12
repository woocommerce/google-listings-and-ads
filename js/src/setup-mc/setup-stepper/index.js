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
	// TODO: get the user's saved step from API backend.
	const [ savedStep, setSavedStep ] = useState( 2 );

	// pageStep is used to control the current step
	// that the user is seeing.
	// pageStep should always be <= maxStep.
	//
	// TODO: the two useStates here should be refactored later
	// when we integrate with API backend later.
	const [ pageStep, setPageStep ] = useState( savedStep );

	const handleSetupAccountsContinue = () => {
		recordSetupMCEvent( 'step1_continue' );
		setPageStep( 2 );
		setSavedStep( Math.max( savedStep, 2 ) );
	};

	const handleChooseAudienceContinue = () => {
		recordSetupMCEvent( 'step2_continue' );
		setPageStep( 3 );
		setSavedStep( Math.max( savedStep, 3 ) );
	};

	const handleStepClick = ( key ) => {
		if ( key <= savedStep ) {
			setPageStep( key );
		}
	};

	return (
		<Stepper
			className="gla-setup-stepper"
			currentStep={ pageStep }
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
					onClick: handleStepClick,
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
					onClick: handleStepClick,
				},
				{
					key: 3,
					label: __(
						'Configure your free listings',
						'google-listings-and-ads'
					),
					content: <SetupFreeListings />,
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default SetupStepper;
