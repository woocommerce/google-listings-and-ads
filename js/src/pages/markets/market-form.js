/**
 * External dependencies
 */
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import checkErrors from './utils/checkErrors';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';

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
	const { saveShippingRates } = useSaveShippingRates();
	const { saveShippingTimes } = useSaveShippingTimes();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createMarket, updateMarket, invalidateResolution } =
		useAppDispatch();

	const extendAdapter = ( formContext ) => {
		return {
			isSaving,
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
				await saveShippingRates( shipping_country_rates );

				const timesForSave =
					countries?.length && shipping_country_times?.length
						? shipping_country_times.map( ( t ) => ( {
								...t,
								time: data.flat_shipping_min_time,
								maxTime: data.flat_shipping_max_time,
						  } ) )
						: shipping_country_times;

				await saveShippingTimes( timesForSave );
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
		const { country, countries } = values;
		const matchesCountry = ( code ) =>
			countries ? countries.includes( code ) : code === country;

		if ( change.name === 'flat_shipping_rate' ) {
			const existingRates = values.shipping_country_rates || [];

			const rates = existingRates.map( ( singleRate ) =>
				matchesCountry( singleRate.country )
					? { ...singleRate, rate: change.value }
					: singleRate
			);

			setValue( 'shipping_country_rates', rates );
		} else if ( change.name === 'offer_free_shipping' ) {
			if ( change.value === false ) {
				const nextValue = ( values.shipping_country_rates || [] ).map(
					( rate ) =>
						matchesCountry( rate.country )
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
			const times = ( values.shipping_country_times || [] ).map(
				( timeEntry ) =>
					matchesCountry( timeEntry.countryCode )
						? {
								...timeEntry,
								...( change.name === 'flat_shipping_min_time'
									? { time: change.value }
									: { maxTime: change.value } ),
						  }
						: timeEntry
			);

			setValue( 'shipping_country_times', times );
		} else if ( change.name === 'free_shipping_threshold' ) {
			const nextValue = ( values.shipping_country_rates || [] ).map( ( rate ) =>
				matchesCountry( rate.country )
					? {
							...rate,
							options: {
								...rate.options,
								free_shipping_threshold: change.value,
							},
					  }
					: rate
			);

			setValue( 'shipping_country_rates', nextValue );
		}
	};

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ initialMarket }
			extendAdapter={ extendAdapter }
			validate={ checkErrors }
			onSubmit={ handleSubmit }
			onChange={ handleChange }
			{ ...adaptiveFormProps }
		/>
	);
};

export default MarketForm;