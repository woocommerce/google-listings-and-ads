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
import checkErrors from '../utils/checkErrors';
import useSettings from '~/hooks/useSettings';
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
			countries, // omit countries from the data sent to the API since it's already included via the primary-market branch below; to be removed once the API is updated to accept countries only there.
			...restValues
		} = values;

		const shipping = buildShippingPayload( values );
		const data = shipping ? { ...restValues, shipping } : restValues;

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

			// Saving shipping above can change which countries are split into
			// their own flat-rate derived secondary markets, so the cached
			// markets list is no longer trustworthy after this point.
			invalidateResolution( 'getTargetAudience', [] );
			invalidateResolution( 'getMarkets', [] );
			onSubmit();
		} catch ( error ) {
			// Every awaited action has already dispatched its own error
			// notice; this catch only keeps the modal open for retry/cancel.
		} finally {
			setIsSaving( false );
		}
	};

	/**
	 * Keeps dependent shipping fields in sync as individual form fields
	 * change. Each market has a single shipping profile now, so this only
	 * ever patches other top-level fields directly — no row materialisation.
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
				flat_shipping_min_time: shipping.flat_time,
				flat_shipping_max_time: shipping.flat_max_time,
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
