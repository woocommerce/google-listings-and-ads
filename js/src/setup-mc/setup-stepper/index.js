/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SetupAccounts from './setup-accounts';
import SetupFreeListings from './setup-free-listings';
import './index.scss';

const SetupStepper = () => {
	// TODO: get the user's current step from API backend.
	const step = 'first';

	return (
		<Stepper
			className="gla-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: 'first',
					label: __(
						'Set up your accounts',
						'google-listings-and-ads'
					),
					description: 'Step item description',
					content: <SetupAccounts />,
				},
				{
					key: 'second',
					label: __(
						'Configure your free listings',
						'google-listings-and-ads'
					),
					description: 'Step item description',
					content: <SetupFreeListings />,
				},
			] }
		/>
	);
};

export default SetupStepper;
