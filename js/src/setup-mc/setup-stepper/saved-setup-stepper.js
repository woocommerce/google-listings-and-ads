/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
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

const SavedSetupStepper = ( props ) => {
	const { savedStep } = props;
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	// pageStep is used to control the current step
	// that the user is seeing.
	// pageStep should always be <= savedStep.
	const [ pageStep, setPageStep ] = useState( savedStep );

	const handleSetupAccountsContinue = () => {
		recordSetupMCEvent( 'step1_continue' );
		setPageStep( '2' );

		if ( parseInt( savedStep, 10 ) < 2 ) {
			updateOptions( {
				gla_setup_mc_saved_step: '2',
			} );
		}
	};

	const handleChooseAudienceContinue = () => {
		recordSetupMCEvent( 'step2_continue' );
		setPageStep( '3' );

		if ( parseInt( savedStep, 10 ) < 3 ) {
			updateOptions( {
				gla_setup_mc_saved_step: 3,
			} );
		}
	};

	const handleStepClick = ( key ) => {
		if ( parseInt( key, 10 ) <= parseInt( savedStep, 10 ) ) {
			setPageStep( key );
		}
	};

	return (
		<Stepper
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

export default SavedSetupStepper;
