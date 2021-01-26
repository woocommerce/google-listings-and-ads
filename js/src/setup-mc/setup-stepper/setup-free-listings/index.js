/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Form } from '@woocommerce/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../data';
import AppSpinner from '../../../components/app-spinner';
import DebounceCallback from '../../../components/debounce-callback';
import StepContent from '../components/step-content';
import StepContentFooter from '../components/step-content-footer';
import ShippingRate from './shipping-rate';
import ShippingTime from './shipping-time';
import TaxRate from './tax-rate';
import PreLaunchChecklist from './pre-launch-checklist';
import Hero from './hero';

const SetupFreeListings = () => {
	const settings = useSelect( ( select ) => {
		return select( STORE_KEY ).getSettings();
	} );
	const { saveSettings } = useDispatch( STORE_KEY );

	if ( ! settings ) {
		return <AppSpinner />;
	}

	// TODO: initial values for the form.
	// the shippingRateOption-rows should first load from previous saved value;
	// if there is no saved value, then it should be set to the countries
	// selected in Step 2 Choose Your Audience.
	const initialValues = {
		shipping_rate: settings.shipping_rate,
		'shippingRateOption-freeShipping': false,
		'shippingRateOption-freeShipping-priceOver': '',
		shippingTimeOption: null,
		'shippingTimeOption-allowGoogleDataCollection': false,
		taxRateOption: null,
		checkWebsiteLive: false,
		checkCheckoutProcess: false,
		checkPaymentMethods: false,
		checkPolicy: false,
		checkContacts: false,
	};

	// TODO: call backend API and display tax rate section
	// only when users selected US in the list of countries.
	const displayTaxRate = true;

	const handleAutosave = ( values ) => {
		saveSettings( values );
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
							<ShippingRate formProps={ formProps } />
							<ShippingTime formProps={ formProps } />
							{ displayTaxRate && (
								<TaxRate formProps={ formProps } />
							) }
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
							<DebounceCallback
								value={ values }
								delay={ 500 }
								onCallback={ handleAutosave }
							/>
						</StepContent>
					);
				} }
			</Form>
		</div>
	);
};

export default SetupFreeListings;
