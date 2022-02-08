/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '.~/components/app-input-price-control';
import EditRateButton from './edit-rate-button';
import AppSpinner from '.~/components/app-spinner';
import CountryNames from '.~/components/free-listings/configure-product-listings/country-names';
import './index.scss';

/**
 * Input control to edit a shipping rate.
 * Consists of a simple input field to adjust the rate
 * and with a modal with a more advanced form to select countries.
 *
 * @param {Object} props
 * @param {AggregatedShippingRate} props.value Aggregate, rat: Array object to be used as the initial value.
 * @param {Array<CountryCode>} props.audienceCountries List of all audience countries.
 * @param {number} props.totalCountyCount Number of all anticipated countries.
 * @param {(newRate: AggregatedShippingRate, deletedCountries: Array<CountryCode>|undefined) => void} props.onChange Called when rate changes.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 */
const CountriesPriceInput = ( {
	value,
	audienceCountries,
	totalCountyCount,
	onChange,
	onDelete,
} ) => {
	const { countries, currency, price } = value;

	if ( ! audienceCountries ) {
		return <AppSpinner />;
	}

	const handleBlur = ( event, numberValue ) => {
		if ( price === numberValue ) {
			return;
		}

		onChange( {
			countries,
			currency,
			price: numberValue,
		} );
	};

	return (
		<div className="gla-countries-price-input">
			<AppInputPriceControl
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
										<CountryNames
											countries={ countries }
											total={ totalCountyCount }
										/>
									),
								}
							) }
						</div>
						<EditRateButton
							audienceCountries={ audienceCountries }
							onChange={ onChange }
							onDelete={ onDelete }
							rate={ value }
						/>
					</div>
				}
				suffix={ currency }
				value={ price }
				onBlur={ handleBlur }
			/>
			{ price === 0 && (
				<div className="gla-input-pill-div">
					<Pill>
						{ __(
							'Free shipping for all orders',
							'google-listings-and-ads'
						) }
					</Pill>
				</div>
			) }
		</div>
	);
};

export default CountriesPriceInput;

/**
 * @typedef {import("../countries-form.js").AggregatedShippingRate} AggregatedShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
