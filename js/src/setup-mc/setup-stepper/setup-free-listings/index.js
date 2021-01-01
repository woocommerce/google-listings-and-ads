/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import StepContent from '../components/step-content';
import StepContentFooter from '../components/step-content-footer';
import ShippingRate from './shipping-rate';
import PreLaunchChecklist from './pre-launch-checklist';
import Hero from './hero';

const SetupFreeListings = () => {
	// TODO: initial values for the form.
	const initialValues = {
		checkWebsiteLive: false,
		checkCheckoutProcess: false,
		checkPaymentMethods: false,
		checkPolicy: false,
		checkContacts: false,
	};

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	// TODO: call backend API when submit form.
	const handleSubmitCallback = () => {};

	return (
		<div className="gla-setup-free-listings">
			<Hero />
			<Form
				initialValues={ initialValues }
				validate={ handleValidate }
				onSubmitCallback={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					const { values, errors, handleSubmit } = formProps;

					const isCompleteSetupDisabled =
						Object.keys( errors ).length >= 1 ||
						! (
							values.checkWebsiteLive &&
							values.checkCheckoutProcess &&
							values.checkPaymentMethods &&
							values.checkPolicy &&
							values.checkContacts
						);

					return (
						<StepContent>
							<ShippingRate />
							<PreLaunchChecklist formProps={ formProps } />
							<StepContentFooter>
								<Button
									isPrimary
									disabled={ isCompleteSetupDisabled }
									onClick={ handleSubmit }
								>
									{ __(
										'Complete setup',
										'google-listings-and-ads'
									) }
								</Button>
							</StepContentFooter>
						</StepContent>
					);
				} }
			</Form>
		</div>
	);
};

export default SetupFreeListings;
