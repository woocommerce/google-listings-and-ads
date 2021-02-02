/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SetupStepper from '../../components/setup-stepper';
import { recordSetupMCEvent } from '../../utils/recordEvent';
import SetupAccounts from './setup-accounts';
import SetupFreeListings from './setup-free-listings';
import ChooseAudience from './choose-audience';
import usePageStep from './usePageStep';

const SavedListingsStepper = ( props ) => {
	const { savedStep } = props;
	const { pageStep, updatePageStep } = usePageStep( savedStep );

	const handleSetupAccountsContinue = () => {
		recordSetupMCEvent( 'step1_continue' );
		updatePageStep( '2' );
	};

	const handleChooseAudienceContinue = () => {
		recordSetupMCEvent( 'step2_continue' );
		updatePageStep( '3' );
	};

	const handleStepClick = ( key ) => {
		if ( parseInt( key, 10 ) <= parseInt( savedStep, 10 ) ) {
			updatePageStep( key );
		}
	};

	return (
		<SetupStepper
			className="gla-setup-stepper"
			currentStep={ pageStep }
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
					key: '3',
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

export default SavedListingsStepper;
