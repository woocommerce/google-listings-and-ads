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
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import useSettings from '~/hooks/useSettings';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';

/**
 * Returns a predicate: "should this country be updated?"
 * - Non-primary market: matches only `values.country`.
 * - Primary market: matches every country in `values.countries`.
 *
 * @param {boolean} isPrimaryMarket
 * @param {Object}  values Current form values.
 * @return {Function} (countryCode: string) => boolean
 */
function getCountryPredicate( isPrimaryMarket, values ) {
	if ( isPrimaryMarket ) {
		const selected = new Set( values.countries || [] );
		return ( code ) => selected.has( code );
	}
	return ( code ) => code === values.country;
}

/**
 * Patches top-level fields and/or the `options` object on matching rate rows.
 *
 * @param {Array}    rates         Current rate rows.
 * @param {Function} isTarget      Predicate — true for rows that should be patched.
 * @param {Object}   [patch]       Top-level fields to merge onto matching rows.
 * @param {Object}   [optionsPatch] Fields to merge into `row.options` on matching rows.
 * @return {Array} New rate array with matching rows patched.
 */
function updateRateRows( rates, isTarget, patch, optionsPatch ) {
	return rates.map( ( rate ) => {
		if ( ! isTarget( rate.country ) ) {
			return rate;
		}

		return {
			...rate,
			...patch,
			...( optionsPatch !== undefined && {
				options: { ...rate.options, ...optionsPatch },
			} ),
		};
	} );
}

/**
 * Patches top-level fields on matching time rows.
 */
function updateTimes( times, isTarget, patch ) {
	return times.map( ( entry ) =>
		isTarget( entry.countryCode ) ? { ...entry, ...patch } : entry
	);
}

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
	children,
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

	const isLoading =
		! hasResolvedShippingRates || ! hasResolvedShippingTimes || ! settings;

	if ( isLoading ) {
		return children( { adapter: { isLoading: true } } );
	}

	const extendAdapter = ( formContext ) => {
		return {
			isSaving,
			isLoading: false,
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
	 * figures out which rows to update (via `getCountryPredicate`) and
	 * applies the right patch depending on which field changed.
	 *
	 * - Non-primary market: only the row for `values.country` is updated.
	 * - Primary market: every row whose country is in `values.countries` is updated.
	 *
	 * @param {Object} change The field change event — `{ name, value }`.
	 * @param {Object} values Current form values snapshot.
	 */
	const handleChange = ( change, values ) => {
		const { setValue } = formRef.current;

		const isTarget = getCountryPredicate( isPrimaryMarket, values );
		const rates = values.shipping_country_rates || [];
		const times = values.shipping_country_times || [];

		switch ( change.name ) {
			case 'flat_shipping_rate': {
				const isFree = change.value === 0;
				setValue(
					'shipping_country_rates',
					updateRateRows(
						rates,
						isTarget,
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
					setValue(
						'shipping_country_rates',
						updateRateRows(
							rates,
							isTarget,
							{},
							{
								free_shipping_threshold: undefined,
							}
						)
					);
				}
				break;

			case 'flat_shipping_min_time':
				setValue(
					'shipping_country_times',
					updateTimes( times, isTarget, { time: change.value } )
				);
				break;

			case 'flat_shipping_max_time':
				setValue(
					'shipping_country_times',
					updateTimes( times, isTarget, { maxTime: change.value } )
				);
				break;

			case 'free_shipping_threshold':
				setValue(
					'shipping_country_rates',
					updateRateRows(
						rates,
						isTarget,
						{},
						{
							free_shipping_threshold: change.value,
						}
					)
				);
				break;
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
			const existingRate = shippingRates?.find(
				( rate ) => rate.country === marketId.toUpperCase() // @TODO: check with BE with the ID is saved to lowercase. Here we need to convert to uppercase
			);
			const existingTime = shippingTimes?.find(
				( time ) => time.countryCode === marketId.toUpperCase()
			);

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
		>
			{ children }
		</AdaptiveForm>
	);
};

export default MarketForm;
