/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import Hero from '.~/components/free-listings/configure-product-listings/hero';
import FormContent from './form-content';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRateFromServerSide
 */

/**
 * Setup step to configure free listings.
 *
 * Copied from {@link .~/setup-mc/setup-stepper/setup-free-listings/index.js},
 * without any save strategy, this is to be bound externaly.
 *
 * @param {Object} props
 * @param {string} props.stepHeader Header text to indicate the step number.
 * @param {Object} props.settings Settings data, if not given AppSpinner will be rendered.
 * @param {(change: {name, value}, values: Object) => void} props.onSettingsChange Callback called with new data once form data is changed. Forwarded from {@link Form.Props.onChangeCallback}
 * @param {Array<ShippingRateFromServerSide>} props.shippingRates Shipping rates data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} props.onShippingRatesChange Callback called with new data once shipping rates are changed. Forwarded from {@link Form.Props.onChangeCallback}
 * @param {function(Object)} props.onContinue Callback called with form data once continue button is clicked.
 */
const SetupFreeListings = ( {
	stepHeader,
	settings,
	shippingRates,
	onSettingsChange = () => {},
	onShippingRatesChange = () => {},
	onContinue = () => {},
} ) => {
	if ( ! settings || ! shippingRates ) {
		return <AppSpinner />;
	}

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	return (
		<div className="gla-setup-free-listings">
			<Hero stepHeader={ stepHeader } />
			<Form
				initialValues={ {
					shipping_rate: settings.shipping_rate,
					offers_free_shipping: settings.offers_free_shipping,
					free_shipping_threshold: settings.free_shipping_threshold,
					shipping_time: settings.shipping_time,
					share_shipping_time: settings.share_shipping_time,
					tax_rate: settings.tax_rate,
					website_live: settings.website_live,
					checkout_process_secure: settings.checkout_process_secure,
					payment_methods_visible: settings.payment_methods_visible,
					refund_tos_visible: settings.refund_tos_visible,
					contact_info_visible: settings.contact_info_visible,
					// Glue shipping rates and times together, as the Form does not support nested structures.
					shipping_country_rates: shippingRates,
				} }
				onChangeCallback={ ( change, newVals ) => {
					// Un-glue form data.
					const {
						shipping_country_rates: newShippingRates,
						...newSettings
					} = newVals;

					switch ( change.name ) {
						case 'shipping_country_rates':
							onShippingRatesChange( newShippingRates );
							break;
						default:
							onSettingsChange( change, newSettings );
					}
				} }
				validate={ handleValidate }
				onSubmitCallback={ onContinue }
			>
				{ ( formProps ) => {
					return <FormContent formProps={ formProps } />;
				} }
			</Form>
		</div>
	);
};

export default SetupFreeListings;
