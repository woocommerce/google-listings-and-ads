/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { pick, noop } from 'lodash';

/**
 * Internal dependencies
 */
import useStoreCountry from '.~/hooks/useStoreCountry';
import AppSpinner from '.~/components/app-spinner';
import Hero from '.~/components/free-listings/configure-product-listings/hero';
import AdaptiveForm from '.~/components/adaptive-form';
import ValidationErrors from '.~/components/validation-errors';
import checkErrors from '.~/components/free-listings/configure-product-listings/checkErrors';
import getOfferFreeShippingInitialValue from '.~/utils/getOfferFreeShippingInitialValue';
import isNonFreeShippingRate from '.~/utils/isNonFreeShippingRate';
import FormContent from './form-content';

/**
 * @typedef {import('.~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRateFromServerSide
 * @typedef {import('.~/data/actions').ShippingTime} ShippingTime
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

const targetAudienceFields = [ 'locale', 'language', 'location', 'countries' ];

/**
 * Field names for settings.
 *
 * If we are adding a new settings field, it should be added into this array.
 */
const settingsFieldNames = [ 'shipping_rate', 'shipping_time', 'tax_rate' ];

/**
 * Get settings object from Form values.
 *
 * This method is used to pick out form fields that are specific to settings.
 * If we are adding a new settings field that will be saved via the settings API,
 * it should be added into `settingsFieldNames`.
 *
 * If a new field is added into the form that is not related to settings (e.g. `offer_free_shipping`)
 * and will NOT be saved via settings API,
 * we do not need to add the field into `settingsFieldNames`,
 * and things should continue to work as expected (e.g. the navigate away prompt).
 *
 * @param {Object} values Form values.
 * @return {Object} Settings object.
 */
const getSettings = ( values ) => {
	return pick( values, settingsFieldNames );
};

/**
 * Setup step to configure free listings.
 *
 * @param {Object} props
 * @param {TargetAudienceData} props.targetAudience Target audience value data to be initialed the form, if not given AppSpinner will be rendered.
 * @param {(targetAudience: TargetAudienceData) => Array<CountryCode>} props.resolveFinalCountries Callback for this component to resolve the given `targetAudience` to the final list of countries.
 * @param {(targetAudience: TargetAudienceData) => void} [props.onTargetAudienceChange] Callback called with new data once target audience data is changed. Forwarded from and {@link Form.Props.onChange}.
 * @param {Object} props.settings Settings data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} [props.onSettingsChange] Callback called with new data once form data is changed. Forwarded from and {@link Form.Props.onChange}.
 * @param {Array<ShippingRateFromServerSide>} props.shippingRates Shipping rates data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} [props.onShippingRatesChange] Callback called with new data once shipping rates are changed. Forwarded from {@link Form.Props.onChange}.
 * @param {Array<ShippingTime>} props.shippingTimes Shipping times data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} [props.onShippingTimesChange] Callback called with new data once shipping times are changed. Forwarded from {@link Form.Props.onChange}.
 * @param {() => void} [props.onContinue] Callback called once continue button is clicked. Could be async. While it's being resolved the form would turn into a saving state.
 * @param {string} [props.submitLabel] Submit button label, to be forwarded to `FormContent`.
 * @param {JSX.Element} props.headerTitle Title in the header block of this setup.
 */
const SetupFreeListings = ( {
	targetAudience,
	resolveFinalCountries,
	onTargetAudienceChange = noop,
	settings,
	onSettingsChange = noop,
	shippingRates,
	onShippingRatesChange = noop,
	shippingTimes,
	onShippingTimesChange = noop,
	onContinue = noop,
	submitLabel,
	headerTitle,
} ) => {
	const formRef = useRef();
	const { code: storeCountryCode } = useStoreCountry();

	if ( ! ( targetAudience && settings && shippingRates && shippingTimes ) ) {
		return <AppSpinner />;
	}

	const handleValidate = ( values ) => {
		const countries = resolveFinalCountries( values );
		const { shipping_country_times: shippingTimesData } = values;

		return checkErrors(
			values,
			shippingTimesData,
			countries,
			storeCountryCode
		);
	};

	const handleChange = ( change, values ) => {
		const { setValue } = formRef.current;

		if ( change.name === 'shipping_country_rates' ) {
			onShippingRatesChange( values.shipping_country_rates );

			// If all the shipping rates are free shipping,
			// we set the offer_free_shipping to undefined,
			// so that when users add a non-free shipping rate,
			// they would need to choose "yes" / "no" for offer_free_shipping.
			if ( ! change.value.some( isNonFreeShippingRate ) ) {
				setValue( 'offer_free_shipping', undefined );
			}
		} else if ( change.name === 'offer_free_shipping' ) {
			// After selecting the 'No' option of the free shipping threshold,
			// Reset all shipping_country_rates.options.free_shipping_threshold.
			if ( change.value === false ) {
				const nextValue = values.shipping_country_rates.map(
					( rate ) => ( {
						...rate,
						options: {
							...rate.options,
							free_shipping_threshold: undefined,
						},
					} )
				);

				setValue( 'shipping_country_rates', nextValue );
			}
		} else if ( change.name === 'shipping_country_times' ) {
			onShippingTimesChange( values.shipping_country_times );
		} else if ( settingsFieldNames.includes( change.name ) ) {
			// The value of `shipping_time` option is determined by the value of `shipping_rate` option.
			// So if the current form change is considered it needs to change `shipping_time` as well,
			// it schedules the processing with `formPropsDelegateeRef` and also skips the call of
			// `onSettingsChange` this time, and lets the call of `onSettingsChange` be triggered
			// when the form change of `shipping_time` happens.
			let shouldTriggerOnChange = true;

			if ( change.name === 'shipping_rate' ) {
				// When shipping rate is 'manual', shipping time should be 'manual' as well;
				// When shipping rate is 'automatic' or 'flat', shipping time should be 'flat'.
				const nextValue = change.value === 'manual' ? 'manual' : 'flat';

				if ( nextValue !== values.shipping_time ) {
					shouldTriggerOnChange = false;
					setValue( 'shipping_time', nextValue );
				}
			}

			if ( shouldTriggerOnChange ) {
				onSettingsChange( getSettings( values ) );
			}
		} else if ( targetAudienceFields.includes( change.name ) ) {
			onTargetAudienceChange( pick( values, targetAudienceFields ) );

			// Only keep shipping data with selected countries.
			[ 'shipping_country_rates', 'shipping_country_times' ].forEach(
				( field ) => {
					const countries = resolveFinalCountries( values );
					const currentValues = values[ field ];
					const nextValues = currentValues.filter( ( el ) =>
						countries.includes( el.country || el.countryCode )
					);
					if ( nextValues.length !== currentValues.length ) {
						setValue( field, nextValues );
					}
				}
			);
		}
	};

	const extendAdapter = ( formContext ) => {
		return {
			audienceCountries: resolveFinalCountries( formContext.values ),
			renderRequestedValidation( key ) {
				if ( formContext.adapter.requestedShowValidation ) {
					return (
						<ValidationErrors
							messages={ formContext.errors[ key ] }
						/>
					);
				}
				return null;
			},
		};
	};

	return (
		<div className="gla-setup-free-listings">
			<Hero headerTitle={ headerTitle } />
			<AdaptiveForm
				ref={ formRef }
				initialValues={ {
					// Fields for target audience.
					locale: targetAudience.locale,
					language: targetAudience.language,
					location: targetAudience.location,
					countries: targetAudience.countries || [],
					// These are the fields for settings.
					shipping_rate: settings.shipping_rate,
					shipping_time: settings.shipping_time,
					tax_rate: settings.tax_rate,
					// This is used in UI only, not used in API.
					offer_free_shipping:
						getOfferFreeShippingInitialValue( shippingRates ),
					// Glue shipping rates and times together, as the Form does not support nested structures.
					shipping_country_rates: shippingRates,
					shipping_country_times: shippingTimes,
				} }
				extendAdapter={ extendAdapter }
				onChange={ handleChange }
				validate={ handleValidate }
				onSubmit={ onContinue }
			>
				<FormContent submitLabel={ submitLabel } />
			</AdaptiveForm>
		</div>
	);
};

export default SetupFreeListings;
