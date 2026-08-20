/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { useAppDispatch } from '~/data';
import { handleApiError } from '~/utils/handleError';
import checkErrors from '../utils/checkErrors';
import useSettings from '~/hooks/useSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import AppSpinner from '~/components/app-spinner';
import checkIsPrimaryMarket from '../utils/isPrimaryMarket';

const CURRENCY_FIELD = 'currency';
const LOCALE_FIELDS = [ 'language', CURRENCY_FIELD ];
const SHIPPING_TIME_FIELDS = [
	'flat_shipping_min_time',
	'flat_shipping_max_time',
];
const FLAT_RATE_FIELDS = [
	CURRENCY_FIELD,
	'flat_shipping_rate',
	'offer_free_shipping',
	'free_shipping_threshold',
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
	const { settings } = useSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createMarket, updateMarket, syncSettings, invalidateResolution } =
		useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const countryNameMap = useCountryKeyNameMap();
	const marketId = initialMarket?.id;
	const isEditing = Boolean( marketId );
	const isPrimaryMarket = isEditing && checkIsPrimaryMarket( initialMarket );

	if ( ! settings ) {
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

	/**
	 * Builds the `shipping` object to nest in the createMarket/updateMarket
	 * payload from the form's single-value shipping fields. Mirrors
	 * fieldsByMethod from resolveInitialMarket: FLAT includes rate + times,
	 * AUTOMATIC includes times only, MANUAL includes neither.
	 *
	 * @param {Object} values Submitted form values.
	 * @return {Object|undefined} The `shipping` object, or undefined for MANUAL.
	 */
	const buildShippingPayload = ( values ) => {
		const { shipping_rate: shippingRateMethod } = settings;

		if ( shippingRateMethod === SHIPPING_RATE_METHOD.FLAT ) {
			return {
				flat_rate: values.flat_shipping_rate,
				free_shipping_threshold: values.offer_free_shipping
					? values.free_shipping_threshold
					: null,
				flat_time: values.flat_shipping_min_time,
				flat_max_time: values.flat_shipping_max_time,
			};
		}

		if ( shippingRateMethod === SHIPPING_RATE_METHOD.AUTOMATIC ) {
			return {
				flat_time: values.flat_shipping_min_time,
				flat_max_time: values.flat_shipping_max_time,
			};
		}

		return undefined;
	};

	const handleSubmit = async ( values ) => {
		const {
			flat_shipping_rate,
			offer_free_shipping,
			free_shipping_threshold,
			flat_shipping_min_time,
			flat_shipping_max_time,
			countries, // only the primary market accepts `countries`; pulled out here so it's added back below only for that case, and left off the payload for every other market.
			...restValues
		} = values;

		const shipping = buildShippingPayload( values );
		const data = shipping ? { ...restValues, shipping } : restValues;

		let mergedIntoPrimary = false;

		try {
			setIsSaving( true );

			if ( marketId ) {
				await updateMarket(
					marketId,
					isPrimaryMarket ? { ...data, countries } : data
				);
			} else {
				// The API compares the submitted shipping against the primary market's own
				// and folds the country in when they match, rather than storing a market
				// that would feed identically. Values absent for the store's method are
				// simply not sent, which the API reads as nothing to compare.
				const response = await createMarket( data );

				mergedIntoPrimary = Boolean( response?.merged_into_primary );
			}

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

			// Saving shipping above changes what the markets list reports for the
			// affected countries, so the cached list is no longer trustworthy.
			invalidateResolution( 'getTargetAudience', [] );
			invalidateResolution( 'getMarkets', [] );

			if ( mergedIntoPrimary ) {
				createNotice(
					'success',
					sprintf(
						// translators: %s: name of the country that joined the primary market.
						__(
							'%s was added to the Primary market, as its configuration matched the existing Primary market settings',
							'google-listings-and-ads'
						),
						countryNameMap[ data.country ] || data.country
					),
					{
						type: 'snackbar',
						isDismissible: true,
					}
				);
			}

			onSubmit();
		} catch ( error ) {
			// Every awaited action has already dispatched its own error
			// notice; this catch only keeps the modal open for retry/cancel.
		} finally {
			setIsSaving( false );
		}
	};

	/**
	 * Clears the free-shipping fields when they no longer apply: switching
	 * the flat rate to 0 (unconditionally free) or turning off
	 * `offer_free_shipping` both make `free_shipping_threshold` stale.
	 *
	 * @param {Object} change The field change event — `{ name, value }`.
	 */
	const handleChange = ( change ) => {
		const { setValue } = formRef.current;

		switch ( change.name ) {
			case 'flat_shipping_rate':
				if ( change.value === 0 ) {
					setValue( 'free_shipping_threshold', undefined );
					setValue( 'offer_free_shipping', false );
				}
				break;

			case 'offer_free_shipping':
				if ( change.value === false ) {
					setValue( 'free_shipping_threshold', undefined );
				}
				break;
		}
	};

	/**
	 * Builds the initial form values by merging defaults with the provided
	 * initialMarket (including its `shipping` object, when present), then
	 * filtering to only the fields relevant to the current shipping method,
	 * store locale configuration, and market type (primary vs. secondary).
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

		const { shipping } = initialMarket;

		const updatedMarket = {
			...defaults,
			...initialMarket,
			...( shipping && {
				flat_shipping_rate: shipping.flat_rate,
				offer_free_shipping: shipping.free_shipping_threshold > 0,
				free_shipping_threshold:
					shipping.free_shipping_threshold ?? undefined,
				flat_shipping_min_time:
					shipping.flat_time ?? defaults.flat_shipping_min_time,
				flat_shipping_max_time:
					shipping.flat_max_time ?? defaults.flat_shipping_max_time,
			} ),
		};

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
