/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

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
 * Input control to edit a shipping rate.
 * Consists of simple input field to adjust the rate
 * and with a modal with more advanced form to select countries.
 *
 * @param {Object} props
 * @param {AggregatedShippingRate} props.value Aggregate, rat: Array object to be used as the initial value.
 * @param {(newRate: AggregatedShippingRate, deletedCountries: Array<CountryCode>|undefined) => void} props.onChange Called when rate changes.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 */
const CountriesPriceInput = ( { value, onChange, onDelete } ) => {
	const { countries, currency, price } = value;
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	const handleRateChange = ( v ) => {
		onChange( {
			countries,
			currency,
			price: v,
		} );
	};

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
				onChange={ handleRateChange }
			/>
		</div>
	);
};

export default CountriesPriceInput;

/**
 * @typedef {import("../countries-form.js").AggregatedShippingRate} AggregatedShippingRate
 * @typedef {import("../countries-form.js").CountryCode} CountryCode
 */
