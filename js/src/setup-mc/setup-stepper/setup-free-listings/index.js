/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '../../../data';
import AppSpinner from '../../../components/app-spinner';
import DebounceCallback from '../../../components/debounce-callback';
import { recordPreLaunchChecklistCompleteEvent } from '../../../utils/recordEvent';
import StepContent from '../components/step-content';
import StepContentFooter from '../components/step-content-footer';
import ShippingRate from './shipping-rate';
import ShippingTime from './shipping-time';
import TaxRate from './tax-rate';
import PreLaunchChecklist from './pre-launch-checklist';
import Hero from './hero';
import isPreLaunchChecklistComplete from './isPreLaunchChecklistComplete';
import useSetupFreeListingsSelect from './useSetupFreeListingsSelect';

const SetupFreeListings = () => {
	const { settings, displayTaxRate } = useSetupFreeListingsSelect();
	const { saveSettings } = useAppDispatch();

	if ( ! settings ) {
		return <AppSpinner />;
	}

	const handleAutosave = ( values ) => {
		saveSettings( values );

		if ( isPreLaunchChecklistComplete( values ) ) {
			recordPreLaunchChecklistCompleteEvent();
		}
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
			{ /* TODO: 'shippingTimeOption-rows' should be removed, and use shipping time API instead. */ }
			<Form
				initialValues={ {
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
				} }
				validate={ handleValidate }
				onSubmitCallback={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					const { values, errors, handleSubmit } = formProps;

					const isCompleteSetupDisabled =
						Object.keys( errors ).length >= 1 ||
						! isPreLaunchChecklistComplete( values );

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
