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
	const initialValues = {};

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
					const { errors, handleSubmit } = formProps;

					return (
						<StepContent>
							<ShippingRate />
							<PreLaunchChecklist />
							<StepContentFooter>
								<Button
									isPrimary
									disabled={ Object.keys( errors ).length }
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
