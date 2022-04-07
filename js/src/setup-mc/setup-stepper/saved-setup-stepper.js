/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { recordSetupMCEvent } from '.~/utils/recordEvent';
import SetupAccounts from './setup-accounts';
import SetupFreeListings from './setup-free-listings';
import ChooseAudience from './choose-audience';
import StoreRequirements from './store-requirements';
import './index.scss';
import stepNameKeyMap from './stepNameKeyMap';

/**
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 * @param {Function} [props.onRefetchSavedStep] Callback when Saved Step is updated
 * @fires gla_setup_mc with `{ target: 'step1_continue' | 'step2_continue' | 'step3_continue', trigger: 'click' }`.
 */
const SavedSetupStepper = ( { savedStep, onRefetchSavedStep = () => {} } ) => {
	const [ step, setStep ] = useState( savedStep );

	const handleSetupAccountsContinue = () => {
		recordSetupMCEvent( 'step1_continue' );
		setStep( stepNameKeyMap.target_audience );
		onRefetchSavedStep();
	};

	const handleChooseAudienceContinue = () => {
		recordSetupMCEvent( 'step2_continue' );
		setStep( stepNameKeyMap.shipping_and_taxes );
		onRefetchSavedStep();
	};

	const handleSetupListingsContinue = () => {
		recordSetupMCEvent( 'step3_continue' );
		setStep( stepNameKeyMap.store_requirements );
		onRefetchSavedStep();
	};

	const handleStepClick = ( stepKey ) => {
		if ( Number( stepKey ) <= Number( savedStep ) ) {
			setStep( stepKey );
		}
	};

	return (
		<Stepper
			className="gla-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: stepNameKeyMap.accounts,
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
					key: stepNameKeyMap.target_audience,
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
					key: stepNameKeyMap.shipping_and_taxes,
					label: __(
						'Configure your product listings',
						'google-listings-and-ads'
					),
					content: (
						<SetupFreeListings
							onContinue={ handleSetupListingsContinue }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: stepNameKeyMap.store_requirements,
					label: __(
						'Confirm store requirements',
						'google-listings-and-ads'
					),
					content: <StoreRequirements />,
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default SavedSetupStepper;
