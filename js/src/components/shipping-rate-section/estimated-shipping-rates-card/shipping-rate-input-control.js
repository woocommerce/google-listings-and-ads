/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import AppButton from '.~/components/app-button';
import AppInputPriceControl from '.~/components/app-input-price-control';
import CountryNames from '.~/components/free-listings/configure-product-listings/country-names';
import EditRateModal from './edit-rate-modal';
import './shipping-rate-input-control.scss';

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
const ShippingRateInputControl = ( {
	value,
	audienceCountries,
	totalCountyCount,
	onChange,
	onDelete,
} ) => {
	const { countries, currency, price } = value;

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
		<div className="gla-shipping-rate-input-control">
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
						<AppButtonModalTrigger
							button={
								<AppButton
									className="gla-shipping-rate-input-control__edit-button"
									isTertiary
								>
									{ __( 'Edit', 'google-listings-and-ads' ) }
								</AppButton>
							}
							modal={
								<EditRateModal
									audienceCountries={ audienceCountries }
									rate={ value }
									onSubmit={ onChange }
									onDelete={ onDelete }
								/>
							}
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

export default ShippingRateInputControl;

/**
 * @typedef {import("./estimated-shipping-rates-card.js").AggregatedShippingRate} AggregatedShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
