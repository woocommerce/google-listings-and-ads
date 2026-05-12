/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { PRIMARY_MARKET_ID } from './constants';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';

/**
 * Form for creating/editing a market. This is a placeholder implementation to be used for testing the end-to-end flow of market creation/editing from the MarketDataViews component, and will be replaced with a real form in a follow-up task.
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
	const { saveShippingRates } = useSaveShippingRates();
	const { saveShippingTimes } = useSaveShippingTimes();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createMarket, updateMarket, invalidateResolution } =
		useAppDispatch();

	const extendAdapter = ( formContext ) => {
		return {
			isSaving,
			renderRequestedValidation( key ) {
				return (
					<ValidationErrors messages={ formContext.errors[ key ] } />
				);
			},
		};
	};

	const handleValidate = ( values ) => {
		const errors = {};

		// @TODO: add error handling here based on difference scenarios
		if (
			values.id === PRIMARY_MARKET_ID &&
			values.countries.length === 0
		) {
			errors.countries = __(
				'Please select at least one country.',
				'google-listings-and-ads'
			);
		}

		return errors;
	};

	const handleSubmit = async ( values ) => {
		const {
			id: marketId,
			shipping_country_rates,
			shipping_country_times,
			countries, // omit countries from the data sent to the API since it's already included in the shipping_country_rates and shipping_country_times, and including it in both places causes confusion; to be removed once the API is updated to accept countries only in the shipping rates and times.
			...data
		} = values;

		try {
			setIsSaving( true );

			if ( marketId ) {
				await updateMarket( marketId, data );
			} else {
				await createMarket( data );
				await saveShippingRates( shipping_country_rates );
				await saveShippingTimes( shipping_country_times );
			}

			invalidateResolution( 'getTargetAudience', [] );
			onSubmit();
		} catch ( error ) {
		} finally {
			setIsSaving( false );
		}
	};

	const handleChange = ( change, values ) => {
		const { setValue } = formRef.current;

		if ( change.name === 'flat_shipping_rate' ) {
			const existingRates = values.shipping_country_rates || [];
			const { country } = values;

			const rates = existingRates.map( ( singleRate ) =>
				singleRate.country === country
					? { ...singleRate, rate: change.value }
					: singleRate
			);

			setValue( 'shipping_country_rates', rates );
		} else if ( change.name === 'shipping_country_rates' ) {
			// If all the shipping rates are free shipping,
			// we set the offer_free_shipping to undefined,
			// so that when users add a non-free shipping rate,
			// they would need to choose "yes" / "no" for offer_free_shipping.
			if ( ! change.value.some( isNonFreeShippingRate ) ) {
				setValue( 'offer_free_shipping', undefined );
			}
		} else if ( change.name === 'offer_free_shipping' ) {
			if ( change.value === false ) {
				const { country } = values;
				const nextValue = values.shipping_country_rates.map(
					( rate ) =>
						rate.country === country
							? {
									...rate,
									options: {
										...rate.options,
										free_shipping_threshold: undefined,
									},
							  }
							: rate
				);

				setValue( 'shipping_country_rates', nextValue );
			}
		} else if (
			change.name === 'flat_shipping_min_time' ||
			change.name === 'flat_shipping_max_time'
		) {
			const { country } = values;
			const times = ( values.shipping_country_times || [] ).map(
				( timeEntry ) =>
					timeEntry.countryCode === country
						? {
								...timeEntry,
								...( change.name === 'flat_shipping_min_time'
									? { time: change.value }
									: { maxTime: change.value } ),
						  }
						: timeEntry
			);

			setValue( 'shipping_country_times', times );
		}
		// } else if ( change.name === 'shipping_country_times' ) {
		// 	// Skip the call of `onShippingTimesChange` if any shipping times are invalid.
		// 	const error = handleValidate( values );
		// 	const isValid = ! error.hasOwnProperty( 'flat_shipping_times' );

		// 	// Skip the call of `onShippingTimesChange` if there are incomplete shipping times.
		// 	// This should only happen during onboarding when the shipping times haven't been stored yet.
		// 	const shippingIsIncomplete = values.shipping_country_times.some(
		// 		( item ) => item.time === null || item.maxTime === null
		// 	);

		// 	if ( ! shippingIsIncomplete && isValid ) {
		// 		onShippingTimesChange( values.shipping_country_times );
		// 	}
		// }
	};

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ initialMarket }
			extendAdapter={ extendAdapter }
			validate={ handleValidate }
			onSubmit={ handleSubmit }
			onChange={ handleChange }
			{ ...adaptiveFormProps }
		/>
	);
};

export default MarketForm;
