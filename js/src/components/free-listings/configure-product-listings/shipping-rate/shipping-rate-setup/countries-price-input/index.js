/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import AppInputControl from '.~/components/app-input-control';
import EditRateButton from './edit-rate-button';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import CountryNames from '.~/components/free-listings/configure-product-listings/country-names';
import './index.scss';
import '../countries-form';

/**
 * The delay between chaning the value an firing onChange callback.
 */
const debounceDelay = 1000;

/**
 * Input control to edit a shipping rate.
 * Consists of a simple input field to adjust the rate
 * and with a modal with a more advanced form to select countries.
 *
 * The changes made via simple input are debounced, to avoid districting users while typing.
 *
 * @param {Object} props
 * @param {AggregatedShippingRate} props.value Aggregate, rat: Array object to be used as the initial value.
 * @param {(newRate: AggregatedShippingRate, deletedCountries: Array<CountryCode>|undefined) => void} props.onChange Called when rate changes.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 */
const CountriesPriceInput = ( { value, onChange, onDelete } ) => {
	const { countries, currency, price } = value;
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	const debouncedOnChange = useDebouncedCallback( ( updatedPrice ) => {
		onChange( {
			countries,
			currency,
			price: updatedPrice,
		} );
	}, debounceDelay );

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-countries-price-input">
			<AppInputControl
				label={
					<div className="label">
						<div>
							{ createInterpolateElement(
								__(
									`Shipping rate for <countries />`,
									'google-listings-and-ads'
								),
								{
									countries: (
										<CountryNames countries={ countries } />
									),
								}
							) }
						</div>
						<EditRateButton
							onChange={ onChange }
							onDelete={ onDelete }
							rate={ value }
						/>
					</div>
				}
				suffix={ currency }
				value={ price }
				onChange={ debouncedOnChange.callback }
			/>
		</div>
	);
};

export default CountriesPriceInput;

/**
 * @typedef {import("../countries-form.js").AggregatedShippingRate} AggregatedShippingRate
 * @typedef {import("../countries-form.js").CountryCode} CountryCode
 */
