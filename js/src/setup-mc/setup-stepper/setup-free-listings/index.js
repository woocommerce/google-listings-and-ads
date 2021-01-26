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
	const { settings, displayTaxRate } = useSelect( ( select ) => {
		const s = select( STORE_KEY ).getSettings();
		const audienceCountryCodes = select(
			STORE_KEY
		).getAudienceSelectedCountryCodes();

		return {
			settings: s,
			displayTaxRate: audienceCountryCodes.includes( 'US' ),
		};
	} );
	const { saveSettings } = useDispatch( STORE_KEY );

	if ( ! settings ) {
		return <AppSpinner />;
	}

	// TODO: 'shippingTimeOption-rows' should be removed, and use shipping time API instead.
	const initialValues = {
		shipping_rate: settings.shipping_rate,
		offers_free_shipping: settings.offers_free_shipping,
		free_shipping_threshold: settings.free_shipping_threshold,
		shipping_time: settings.shipping_time,
		'shippingTimeOption-rows': [],
		share_shipping_time: settings.share_shipping_time,
		tax_rate: settings.tax_rate,
		website_live: settings.website_live,
		checkout_process_secure: settings.checkout_process_secure,
		payment_methods_visible: settings.payment_methods_visible,
		refund_tos_visible: settings.refund_tos_visible,
		contact_info_visible: settings.contact_info_visible,
	};

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
							values.website_live &&
							values.checkout_process_secure &&
							values.payment_methods_visible &&
							values.refund_tos_visible &&
							values.contact_info_visible
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
