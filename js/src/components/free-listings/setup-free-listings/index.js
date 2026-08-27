/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { createSlotFill } from '@wordpress/components';
import { pick, noop } from 'lodash';

/**
 * Internal dependencies
 */
import {
	DEFAULT_SHIPPING_MIN_TIME,
	DEFAULT_SHIPPING_MAX_TIME,
} from '~/constants';
import AppSpinner from '~/components/app-spinner';
import AppButton from '~/components/app-button';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import checkErrors from '~/components/free-listings/configure-product-listings/checkErrors';
import getOfferFreeShippingInitialValue from '~/utils/getOfferFreeShippingInitialValue';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import FormContent from './form-content';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import { TARGET_AUDIENCE_FIELDS } from '../choose-audience-section/constants';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').ShippingRate} ShippingRateFromServerSide
 * @typedef {import('~/data/actions').ShippingTime} ShippingTime
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Field names for settings.
 *
 * If we are adding a new settings field, it should be added into this array.
 */
const settingsFieldNames = [ 'shipping_rate', 'shipping_time' ];

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

const alwaysTrue = () => true;

const { Fill, Slot } = createSlotFill( 'gla/SetupFreeListings/SubmitButton' );

/**
 * Setup step to configure product feed.
 *
 * Note that this component requires to specify the location where it wants to
 * render its submit button via `<SetupFreeListings.SubmitButton />`.
 *
 * @param {Object} props
 * @param {TargetAudienceData} props.targetAudience Target audience value data to be initialed the form, if not given AppSpinner will be rendered.
 * @param {(targetAudience: TargetAudienceData) => Array<CountryCode>} props.resolveFinalCountries Callback for this component to resolve the given `targetAudience` to the final list of countries.
 * @param {(targetAudience: TargetAudienceData) => void} [props.onTargetAudienceChange] Callback called with new data once target audience data is changed.
 * @param {Object} props.settings Settings data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} [props.onSettingsChange] Callback called with new data once form data is changed.
 * @param {Array<ShippingRateFromServerSide>} props.shippingRates Shipping rates data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} [props.onShippingRatesChange] Callback called with new data once shipping rates are changed.
 * @param {Array<ShippingTime>} props.shippingTimes Shipping times data, if not given AppSpinner will be rendered.
 * @param {(newValue: Object) => void} [props.onShippingTimesChange] Callback called with new data once shipping times are changed.
 * @param {() => boolean | Promise<boolean>} [props.onRequestSubmit] Callback called before the form is submitted. If it returns false, the form will not be submitted.
 * @param {() => void} [props.onContinue] Callback called once continue button is clicked. Could be async. While it's being resolved the form would turn into a saving state.
 * @param {string} props.submitLabel Submit button label.
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
	onRequestSubmit = alwaysTrue,
	onContinue = noop,
	submitLabel,
} ) => {
	const formRef = useRef();
	const { code: currencyCode } = useStoreCurrency();

	// AdaptiveForm ignores `initialValues` changes after mount. When SavedSetupStepper
	// auto-saves default shipping times (e.g. after the wp_gla_shipping_times table is
	// cleared), the store updates and `shippingTimes` arrives as a new prop, but the
	// form's internal `shipping_country_times` stays stale. This effect detects the
	// empty → non-empty transition and pushes the new value into the live form state.
	// skipNextTimesChangeRef prevents the resulting handleChange from firing a redundant
	// saveShippingTimes — the data was just saved by SavedSetupStepper moments before.
	const prevShippingTimesRef = useRef( shippingTimes );
	const skipNextTimesChangeRef = useRef( false );
	useEffect( () => {
		const prev = prevShippingTimesRef.current;
		prevShippingTimesRef.current = shippingTimes;

		if ( ! formRef.current || ! shippingTimes ) {
			return;
		}

		if ( prev?.length === 0 && shippingTimes.length > 0 ) {
			skipNextTimesChangeRef.current = true;
			formRef.current.setValue( 'shipping_country_times', shippingTimes );
		}
	}, [ shippingTimes ] );

	if ( ! ( targetAudience && settings && shippingRates && shippingTimes ) ) {
		return <AppSpinner />;
	}

	const handleValidate = ( values ) => {
		return checkErrors( values );
	};

	const handleChange = ( change, values ) => {
		const { setValue } = formRef.current;

		if ( change.name === 'flat_shipping_rate' ) {
			// Translate the single flat rate into the per-country array the API expects.
			// Preserve any existing free_shipping_threshold per country, unless the
			// new rate is free (0), in which case clear the threshold.
			const isFree = change.value === 0;
			const countries = resolveFinalCountries( values );
			const existingByCountry = new Map(
				values.shipping_country_rates.map( ( r ) => [ r.country, r ] )
			);
			const rates = countries.map( ( country ) => ( {
				options: {
					free_shipping_threshold: isFree
						? undefined
						: existingByCountry.get( country )?.options
								?.free_shipping_threshold,
				},
				country,
				currency: currencyCode,
				rate: change.value,
			} ) );

			setValue( 'shipping_country_rates', rates );
		} else if ( change.name === 'shipping_country_rates' ) {
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
		} else if (
			change.name === 'flat_shipping_min_time' ||
			change.name === 'flat_shipping_max_time'
		) {
			const countries = resolveFinalCountries( values );
			const minTime =
				change.name === 'flat_shipping_min_time'
					? change.value
					: values.flat_shipping_min_time;
			const maxTime =
				change.name === 'flat_shipping_max_time'
					? change.value
					: values.flat_shipping_max_time;
			const times = countries.map( ( countryCode ) => ( {
				countryCode,
				time: minTime,
				maxTime,
			} ) );

			setValue( 'shipping_country_times', times );
		} else if ( change.name === 'shipping_country_times' ) {
			// Skip the save when the change was triggered by syncing times from the store
			// (see skipNextTimesChangeRef above) — the data is already persisted.
			if ( skipNextTimesChangeRef.current ) {
				skipNextTimesChangeRef.current = false;
				return;
			}

			// Skip the call of `onShippingTimesChange` if any shipping times are invalid.
			const error = handleValidate( values );
			const isValid = ! error.hasOwnProperty( 'flat_shipping_times' );

			// Skip the call of `onShippingTimesChange` if there are incomplete shipping times.
			// This should only happen during onboarding when the shipping times haven't been stored yet.
			const shippingIsIncomplete = values.shipping_country_times.some(
				( item ) => item.time === null || item.maxTime === null
			);

			if ( ! shippingIsIncomplete && isValid ) {
				onShippingTimesChange( values.shipping_country_times );
			}
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
		} else if ( TARGET_AUDIENCE_FIELDS.includes( change.name ) ) {
			onTargetAudienceChange( pick( values, TARGET_AUDIENCE_FIELDS ) );

			// Sync shipping_country_rates with the updated audience countries.
			const audienceCountries = resolveFinalCountries( values );

			// Filter removed countries AND fill in newly added countries using the current flat rate.
			const filteredRates = values.shipping_country_rates.filter(
				( shippingCountryRate ) =>
					audienceCountries.includes( shippingCountryRate.country )
			);
			const missingCountries = audienceCountries.filter(
				( country ) =>
					! filteredRates.some( ( rate ) => rate.country === country )
			);
			const existingThreshold = filteredRates.find(
				isNonFreeShippingRate
			)?.options?.free_shipping_threshold;
			const nextRates =
				values.flat_shipping_rate !== undefined &&
				missingCountries.length > 0
					? [
							...filteredRates,
							...missingCountries.map( ( country ) => ( {
								options: {
									free_shipping_threshold: existingThreshold,
								},
								country,
								currency: currencyCode,
								rate: values.flat_shipping_rate,
							} ) ),
					  ]
					: filteredRates;
			if ( nextRates.length !== values.shipping_country_rates.length ) {
				setValue( 'shipping_country_rates', nextRates );
			}

			// For times: filter removed countries AND add newly added countries.
			const filteredTimes = values.shipping_country_times.filter(
				( shippingTime ) =>
					audienceCountries.includes( shippingTime.countryCode )
			);
			const missingTimesCountries = audienceCountries.filter(
				( country ) =>
					! filteredTimes.some(
						( shippingTime ) => shippingTime.countryCode === country
					)
			);
			const nextTimes =
				values.flat_shipping_min_time !== null &&
				values.flat_shipping_max_time !== null &&
				missingTimesCountries.length > 0
					? [
							...filteredTimes,
							...missingTimesCountries.map( ( countryCode ) => ( {
								countryCode,
								time: values.flat_shipping_min_time,
								maxTime: values.flat_shipping_max_time,
							} ) ),
					  ]
					: filteredTimes;

			if ( nextTimes.length !== values.shipping_country_times.length ) {
				setValue( 'shipping_country_times', nextTimes );
			}
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
			<AdaptiveForm
				extendAdapter={ extendAdapter }
				initialValues={ {
					// Fields for target audience.
					locale: targetAudience.locale,
					language: targetAudience.language,
					location: targetAudience.location,
					countries: targetAudience.countries || [],
					// These are the fields for settings.
					shipping_rate: settings.shipping_rate,
					shipping_time: settings.shipping_time,
					// This is used in UI only, not used in API.
					offer_free_shipping:
						getOfferFreeShippingInitialValue( shippingRates ),
					// UI-only scalar; assumes all countries share the same rate (flat rate mode).
					// Derived from the first entry; the full per-country array is in shipping_country_rates.
					flat_shipping_rate: shippingRates?.[ 0 ]?.rate,
					// Simple flat time values for all countries (UI only, derived from shippingTimes).
					flat_shipping_min_time:
						shippingTimes?.[ 0 ]?.time ?? DEFAULT_SHIPPING_MIN_TIME,
					flat_shipping_max_time:
						shippingTimes?.[ 0 ]?.maxTime ??
						DEFAULT_SHIPPING_MAX_TIME,
					// Glue shipping rates and times together, as the Form does not support nested structures.
					shipping_country_rates: shippingRates,
					shipping_country_times: shippingTimes,
				} }
				onChange={ handleChange }
				onSubmit={ onContinue }
				ref={ formRef }
				validate={ handleValidate }
			>
				{ ( formContext ) => {
					const { isValidForm, handleSubmit, adapter } = formContext;
					const handleSubmitClick = async ( event ) => {
						if ( isValidForm ) {
							if ( ! ( await onRequestSubmit() ) ) {
								return;
							}
							return handleSubmit( event );
						}

						adapter.showValidation();
					};

					return (
						<>
							<FormContent />
							<Fill>
								<AppButton
									loading={ adapter.isSubmitting }
									onClick={ handleSubmitClick }
									isPrimary
								>
									{ submitLabel }
								</AppButton>
							</Fill>
						</>
					);
				} }
			</AdaptiveForm>
		</div>
	);
};

SetupFreeListings.SubmitButton = Slot;

export default SetupFreeListings;
