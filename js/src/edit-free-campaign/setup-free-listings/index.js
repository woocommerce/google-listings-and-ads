/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import Hero from '.~/components/free-listings/configure-product-listings/hero';
import checkErrors from '.~/components/free-listings/configure-product-listings/checkErrors';
import getOfferFreeShippingInitialValue from '.~/utils/getOfferFreeShippingInitialValue';
import FormContent from './form-content';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRateFromServerSide
 * @typedef {import('.~/data/actions').ShippingTime} ShippingTime
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Get settings object from Form values.
 *
 * @param {Object} values Form values.
 * @return {Object} Settings object.
 */
const getSettings = ( values ) => {
	return {
		shipping_rate: values.shipping_rate,
		shipping_time: values.shipping_time,
		tax_rate: values.tax_rate,
		website_live: values.website_live,
		checkout_process_secure: values.checkout_process_secure,
		payment_methods_visible: values.payment_methods_visible,
		refund_tos_visible: values.refund_tos_visible,
		contact_info_visible: values.contact_info_visible,
	};
};

/**
 * Setup step to configure free listings.
 *
 * Copied from {@link .~/setup-mc/setup-stepper/setup-free-listings/index.js},
 * without any save strategy, this is to be bound externaly.
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countries List of available countries to be forwarded to FormContent.
 * @param {Object} props.settings Settings data, if not given AppSpinner will be rendered.
 * @param {(change: {name, value}, values: Object) => void} props.onSettingsChange Callback called with new data once form data is changed. Forwarded from and {@link Form.Props.onChange}.
 * @param {Array<ShippingRateFromServerSide>} props.shippingRates Shipping rates data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} props.onShippingRatesChange Callback called with new data once shipping rates are changed. Forwarded from {@link Form.Props.onChange}.
 * @param {Array<ShippingTime>} props.shippingTimes Shipping times data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} props.onShippingTimesChange Callback called with new data once shipping times are changed. Forwarded from {@link Form.Props.onChange}.
 * @param {function(Object)} props.onContinue Callback called with form data once continue button is clicked. Could be async. While it's being resolved the form would turn into a saving state.
 * @param {string} [props.submitLabel] Submit button label, to be forwarded to `FormContent`.
 */
const SetupFreeListings = ( {
	countries,
	settings,
	onSettingsChange = () => {},
	shippingRates,
	onShippingRatesChange = () => {},
	shippingTimes,
	onShippingTimesChange = () => {},
	onContinue = () => {},
	submitLabel,
} ) => {
	const [ saving, setSaving ] = useState( false );

	if ( ! settings || ! shippingRates || ! shippingTimes || ! countries ) {
		return <AppSpinner />;
	}

	const handleValidate = ( values ) => {
		const {
			shipping_country_rates: shippingRatesData,
			shipping_country_times: shippingTimesData,
		} = values;

		return checkErrors(
			values,
			shippingRatesData,
			shippingTimesData,
			countries
		);
	};

	const handleSubmit = async () => {
		setSaving( true );
		await onContinue();
		setSaving( false );
	};

	const handleChange = ( change, newVals ) => {
		// Un-glue form data.
		const {
			shipping_country_rates: newShippingRates,
			shipping_country_times: newShippingTimes,
		} = newVals;

		switch ( change.name ) {
			case 'shipping_country_rates':
				onShippingRatesChange( newShippingRates );
				break;
			case 'shipping_country_times':
				onShippingTimesChange( newShippingTimes );
				break;
			default:
				onSettingsChange( change, getSettings( newVals ) );
		}
	};

	return (
		<div className="gla-setup-free-listings">
			<Hero />
			<Form
				initialValues={ {
					shipping_rate: settings.shipping_rate,
					shipping_time: settings.shipping_time,
					tax_rate: settings.tax_rate,
					website_live: settings.website_live,
					checkout_process_secure: settings.checkout_process_secure,
					payment_methods_visible: settings.payment_methods_visible,
					refund_tos_visible: settings.refund_tos_visible,
					contact_info_visible: settings.contact_info_visible,
					// Glue shipping rates and times together, as the Form does not support nested structures.
					shipping_country_rates: shippingRates,
					offer_free_shipping: getOfferFreeShippingInitialValue(
						shippingRates
					),
					shipping_country_times: shippingTimes,
				} }
				onChange={ handleChange }
				validate={ handleValidate }
				onSubmit={ handleSubmit }
			>
				{ ( formProps ) => {
					return (
						<FormContent
							formProps={ formProps }
							countries={ countries }
							submitLabel={ submitLabel }
							saving={ saving }
						/>
					);
				} }
			</Form>
		</div>
	);
};

export default SetupFreeListings;
