/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { useAppDispatch } from '~/data';
import { handleApiError } from '~/utils/handleError';
import {
	getTargetCountries,
	ensureRateRows,
	ensureTimeRows,
	updateTimes,
	updateRateRows,
} from '../utils/shipping-rows';
import checkErrors from '../utils/checkErrors';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import useSettings from '~/hooks/useSettings';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import AppSpinner from '~/components/app-spinner';
import checkIsPrimaryMarket from '../utils/isPrimaryMarket';

const CURRENCY_FIELD = 'currency';
const LOCALE_FIELDS = [ 'language', CURRENCY_FIELD ];
const SHIPPING_TIME_FIELDS = [
	'flat_shipping_min_time',
	'flat_shipping_max_time',
	'shipping_country_times',
];
const FLAT_RATE_FIELDS = [
	CURRENCY_FIELD,
	'flat_shipping_rate',
	'offer_free_shipping',
	'free_shipping_threshold',
	'shipping_country_rates',
];

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
	const { code: storeCurrencyCode } = useStoreCurrency();
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
	const { createMarket, updateMarket, syncSettings, invalidateResolution } =
		useAppDispatch();
	const marketId = initialMarket?.id;
	const isEditing = Boolean( marketId );
	const isPrimaryMarket = isEditing && checkIsPrimaryMarket( initialMarket );

	const isLoading =
		! hasResolvedShippingRates || ! hasResolvedShippingTimes || ! settings;

	if ( isLoading ) {
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
			shipping_country_rates: shippingCountryRates,
			shipping_country_times: shippingCountryTimes,
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

			// Mirror fieldsByMethod from resolveInitialMarket: FLAT includes
			// rates + times, AUTOMATIC includes times only, MANUAL includes neither.
			const { shipping_rate: shippingRateMethod } = settings;
			const saves = [];
			// Countries in the store that are outside the primary target
			// audience belong to secondary markets — exclude them so they
			// are never deleted when saving the primary market.
			const excludedCountryCodes = isPrimaryMarket
				? shippingRates
						.filter(
							( shippingRate ) =>
								! countries.includes( shippingRate.country )
						)
						.map( ( shippingRate ) => shippingRate.country )
				: [];

			if ( shippingRateMethod === SHIPPING_RATE_METHOD.FLAT ) {
				saves.push(
					saveShippingRates(
						shippingCountryRates,
						excludedCountryCodes
					)
				);
			}
			if ( shippingRateMethod !== SHIPPING_RATE_METHOD.MANUAL ) {
				// Times use `countryCode`; re-derive from the times store for
				// correctness (rates and times may cover different country sets).
				const excludedTimeCountryCodes = isPrimaryMarket
					? shippingTimes
							.filter(
								( shippingTime ) =>
									! countries.includes(
										shippingTime.countryCode
									)
							)
							.map( ( shippingTime ) => shippingTime.countryCode )
					: [];
				saves.push(
					saveShippingTimes(
						shippingCountryTimes,
						excludedTimeCountryCodes
					)
				);
			}
			await Promise.all( saves );

			// Always sync after a successful save: creating or updating a
			// market changes shipping data on the server (target audience,
			// adopted rate/time rows) even when the form itself saved no
			// shipping rates or times.
			try {
				await syncSettings();
			} catch ( error ) {
				handleApiError(
					error,
					__(
						'There was an error synchronizing settings to Google Merchant Center. Please try again.',
						'google-listings-and-ads'
					)
				);
				throw error;
			}

			invalidateResolution( 'getTargetAudience', [] );
			onSubmit();
		} catch ( error ) {
			// Every awaited action has already dispatched its own error
			// notice; this catch only keeps the modal open for retry/cancel.
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

		switch ( change.name ) {
			case 'flat_shipping_rate': {
				const rates = ensureRateRows(
					rawRates,
					targetCountries,
					storeCurrencyCode
				);
				const isFree = change.value === 0;

				setValue(
					'shipping_country_rates',
					updateRateRows(
						rates,
						targetCountries,
						{ rate: change.value },
						isFree
							? { free_shipping_threshold: undefined }
							: undefined
					)
				);

				if ( isFree ) {
					setValue( 'free_shipping_threshold', undefined );
					setValue( 'offer_free_shipping', false );
				}
				break;
			}

			case 'offer_free_shipping':
				if ( change.value === false ) {
					// Clearing the threshold — don't materialise rows just to unset.
					setValue(
						'shipping_country_rates',
						updateRateRows(
							rawRates,
							targetCountries,
							{},
							{
								free_shipping_threshold: undefined,
							}
						)
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
					storeCurrencyCode
				);
				setValue(
					'shipping_country_rates',
					updateRateRows(
						rates,
						targetCountries,
						{},
						{
							free_shipping_threshold: change.value,
						}
					)
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
		const { shipping_rate, shipping_time } = settings;

		const defaults = {
			shipping_rate,
			shipping_time,
			country: null,
			flat_shipping_rate: null,
			language: [],
			currency: [],
			offer_free_shipping: false,
			free_shipping_threshold: null,
			flat_shipping_min_time: 1,
			flat_shipping_max_time: 5,
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
				country: editingCountry,
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
		 * are kept in sync whenever the user saves. The same entry is also used
		 * as the starting point when adding a new secondary market.
		 */
		if ( isPrimaryMarket || ! isEditing ) {
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

		const { isMultiLingualStore } = glaData;
		const audienceField = isPrimaryMarket ? 'countries' : 'country';
		const fieldsByMethod = {
			[ SHIPPING_RATE_METHOD.MANUAL ]: [
				...( isPrimaryMarket || isMultiLingualStore
					? [ audienceField ]
					: [] ),
				...( isMultiLingualStore ? LOCALE_FIELDS : [] ),
			],
			[ SHIPPING_RATE_METHOD.FLAT ]: [
				audienceField,
				...FLAT_RATE_FIELDS,
				...SHIPPING_TIME_FIELDS,
			],
			[ SHIPPING_RATE_METHOD.AUTOMATIC ]: [
				audienceField,
				...( isMultiLingualStore ? LOCALE_FIELDS : [] ),
				...SHIPPING_TIME_FIELDS,
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
