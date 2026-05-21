/**
 * External dependencies
 */
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { useAppDispatch } from '~/data';
import { PRIMARY_MARKET_ID } from './constants';
import checkErrors from './utils/checkErrors';
import {
	getTargetCountries,
	ensureRateRows,
	ensureTimeRows,
	updateRates,
	updateRateOptions,
	updateTimes,
} from './utils/shipping-rows';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import useSettings from '~/hooks/useSettings';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import AppSpinner from '~/components/app-spinner';

/**
 * Form component for creating or editing a market.
 * This component is used within the AddMarketModal and EditMarketModal.
 *
 * @param {Object} props
 * @param {Object} props.initialMarket Initial values to populate the form with. Can be empty when creating a new market.
 * @param {Function} props.onSubmit Callback function to handle form submission.
 * @return {JSX.Element} The rendered form.
 */
const MarketForm = ( {
	initialMarket = {},
	onSubmit,
	...adaptiveFormProps
} ) => {
	const formRef = useRef();
	const { settings } = useSettings();
	const {
		data: shippingRates,
		hasFinishedResolution: hasResolvedShippingRates,
	} = useShippingRates();
	const {
		hasFinishedResolution: hasResolvedShippingTimes,
		data: shippingTimes,
	} = useShippingTimes();
	const { saveShippingRates } = useSaveShippingRates();
	const { saveShippingTimes } = useSaveShippingTimes();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createMarket, updateMarket, invalidateResolution } =
		useAppDispatch();
	const marketId = initialMarket?.id;
	const isEditing = Boolean( marketId );
	const isPrimaryMarket = isEditing && marketId === PRIMARY_MARKET_ID;

	if (
		! hasResolvedShippingRates ||
		! hasResolvedShippingTimes ||
		! settings
	) {
		return <AppSpinner />;
	}

	const extendAdapter = ( formContext ) => {
		return {
			isSaving,
			isEditing,
			isPrimaryMarket,
			renderRequestedValidation( key ) {
				if ( ! formContext.adapter.requestedShowValidation ) {
					return null;
				}

				return (
					<ValidationErrors messages={ formContext.errors[ key ] } />
				);
			},
		};
	};

	const handleSubmit = async ( values ) => {
		const {
			shipping_country_rates,
			shipping_country_times,
			countries, // omit countries from the data sent to the API since it's already included in the shipping_country_rates and shipping_country_times, and including it in both places causes confusion; to be removed once the API is updated to accept countries only in the shipping rates and times.
			...data
		} = values;

		try {
			setIsSaving( true );

			if ( marketId ) {
				await updateMarket(
					marketId,
					isPrimaryMarket ? { ...data, countries } : data
				);
			} else {
				await createMarket( data );
			}

			await saveShippingRates( shipping_country_rates );
			await saveShippingTimes( shipping_country_times );

			invalidateResolution( 'getTargetAudience', [] );
			onSubmit();
		} catch ( error ) {
			// Do nothing. Keep the modal open.
		} finally {
			setIsSaving( false );
		}
	};

	/**
	 * Keeps the shipping rate and time collections in sync as individual
	 * form fields change.
	 *
	 * Several fields (e.g. flat_shipping_rate) are displayed as a single
	 * input but must be written into every relevant row of
	 * `shipping_country_rates` or `shipping_country_times`. This handler
	 * resolves the target countries (via `getTargetCountries`), materialises
	 * missing rows where needed, and applies the right patch.
	 *
	 * - Non-primary market: only the row for `values.country` is updated.
	 * - Primary market: every row whose country is in `values.countries` is updated.
	 *
	 * @param {Object} change The field change event — `{ name, value }`.
	 * @param {Object} values Current form values snapshot.
	 */
	const handleChange = ( change, values ) => {
		const { setValue } = formRef.current;

		const targetCountries = getTargetCountries( isPrimaryMarket, values );
		const rawRates = values.shipping_country_rates || [];
		const rawTimes = values.shipping_country_times || [];
		const { currency } = values;

		switch ( change.name ) {
			case 'flat_shipping_rate': {
				const rates = ensureRateRows(
					rawRates,
					targetCountries,
					currency
				);
				setValue(
					'shipping_country_rates',
					updateRates( rates, targetCountries, {
						rate: change.value,
					} )
				);
				break;
			}

			case 'offer_free_shipping':
				if ( change.value === false ) {
					// Clearing the threshold — don't materialise rows just to unset.
					setValue(
						'shipping_country_rates',
						updateRateOptions( rawRates, targetCountries, {
							free_shipping_threshold: undefined,
						} )
					);
				}
				break;

			case 'flat_shipping_min_time': {
				const times = ensureTimeRows( rawTimes, targetCountries );
				setValue(
					'shipping_country_times',
					updateTimes( times, targetCountries, {
						time: change.value,
					} )
				);
				break;
			}

			case 'flat_shipping_max_time': {
				const times = ensureTimeRows( rawTimes, targetCountries );
				setValue(
					'shipping_country_times',
					updateTimes( times, targetCountries, {
						maxTime: change.value,
					} )
				);
				break;
			}

			case 'free_shipping_threshold': {
				const rates = ensureRateRows(
					rawRates,
					targetCountries,
					currency
				);
				setValue(
					'shipping_country_rates',
					updateRateOptions( rates, targetCountries, {
						free_shipping_threshold: change.value,
					} )
				);
				break;
			}
		}
	};

	/**
	 * Builds the initial form values by merging defaults, the provided initialMarket,
	 * and live shipping data, then filtering to only the fields relevant to the current
	 * shipping method, store locale configuration, and market type (primary vs. secondary).
	 *
	 * @return {Object} Filtered initial values for AdaptiveForm.
	 */
	const resolveInitialMarket = () => {
		const defaults = {
			country: null,
			flat_shipping_rate: null,
			offer_free_shipping: false,
			free_shipping_threshold: null,
			flat_shipping_min_time: null,
			flat_shipping_max_time: null,
		};

		let updatedMarket = {
			...defaults,
			...initialMarket,
			shipping_country_rates: shippingRates,
			shipping_country_times: shippingTimes,
		};

		if ( isEditing ) {
			// `initialMarket.country` is the canonical ISO code from the backend
			// (uppercase). The market `id` is `sanitize_title(country)`
			// (lowercased), so deriving the lookup country from `id` would be
			// fragile — use the country field directly.
			const editingCountry = initialMarket.country;
			const existingRate = editingCountry
				? shippingRates?.find(
						( rate ) => rate.country === editingCountry
				  )
				: undefined;
			const existingTime = editingCountry
				? shippingTimes?.find(
						( time ) => time.countryCode === editingCountry
				  )
				: undefined;

			updatedMarket = {
				...updatedMarket,
				...( existingRate && {
					flat_shipping_rate: existingRate.rate,
					offer_free_shipping:
						existingRate.options?.free_shipping_threshold > 0,
					free_shipping_threshold:
						existingRate.options?.free_shipping_threshold ??
						undefined,
				} ),
				...( existingTime && {
					flat_shipping_min_time: existingTime.time,
					flat_shipping_max_time: existingTime.maxTime,
				} ),
			};
		}

		/*
		 * For the primary market, all countries share the same shipping settings,
		 * so the form shows a single set of fields rather than per-country rows.
		 * We seed those fields from the first stored rate/time entry as a
		 * representative value — any row would give the same result since they
		 * are kept in sync whenever the user saves.
		 */
		if ( isPrimaryMarket ) {
			const firstShippingRate = shippingRates?.[ 0 ];
			const firstShippingTime = shippingTimes?.[ 0 ];
			updatedMarket = {
				...updatedMarket,
				...( firstShippingRate && {
					flat_shipping_rate: firstShippingRate.rate,
					offer_free_shipping:
						firstShippingRate.options?.free_shipping_threshold > 0,
					free_shipping_threshold:
						firstShippingRate.options?.free_shipping_threshold ??
						undefined,
				} ),
				...( firstShippingTime && {
					flat_shipping_min_time: firstShippingTime.time,
					flat_shipping_max_time: firstShippingTime.maxTime,
				} ),
			};
		}

		const { shipping_rate } = settings;
		const { isMultiLingualStore } = glaData;

		const audienceField = isPrimaryMarket ? 'countries' : 'country';
		const localeFields = [ 'language', 'currency' ];
		const shippingTimeFields = [
			'flat_shipping_min_time',
			'flat_shipping_max_time',
			'shipping_country_times',
		];
		const flatRateFields = [
			'flat_shipping_rate',
			'offer_free_shipping',
			'free_shipping_threshold',
			'shipping_country_rates',
		];

		const fieldsByMethod = {
			[ SHIPPING_RATE_METHOD.MANUAL ]: [
				...( isPrimaryMarket || isMultiLingualStore
					? [ audienceField ]
					: [] ),
				...( isMultiLingualStore ? localeFields : [] ),
			],
			[ SHIPPING_RATE_METHOD.FLAT ]: [
				audienceField,
				...flatRateFields,
				...shippingTimeFields,
			],
			[ SHIPPING_RATE_METHOD.AUTOMATIC ]: [
				audienceField,
				...( isMultiLingualStore ? localeFields : [] ),
				...shippingTimeFields,
			],
		};

		const allowedFields = new Set( [
			'id',
			'shipping_rate',
			'shipping_time',
			...( fieldsByMethod[ shipping_rate ] ?? [] ),
		] );

		return Object.fromEntries(
			Object.entries( updatedMarket ).filter( ( [ key ] ) => {
				return allowedFields.has( key );
			} )
		);
	};

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ {
				...resolveInitialMarket(),
				// Temporary since the BE needs those fields
				language: initialMarket.language,
				currency: initialMarket.currency,
			} }
			extendAdapter={ extendAdapter }
			validate={ checkErrors }
			onSubmit={ handleSubmit }
			onChange={ handleChange }
			{ ...adaptiveFormProps }
		/>
	);
};

export default MarketForm;
