/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import ShippingRateInputControl from './shipping-rate-input-control';

/**
 * @typedef { import("~/data/actions").CountryCode } CountryCode
 */

/**
 * The "Estimated shipping rates" card to provide shipping rates for individual countries,
 * with an UI, that allows to aggregate countries with the same rate.
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.audienceCountries Array of country codes of all audience countries.
 * @param {number} props.value The shipping rate this control is responsible for.
 * @param {JSX.Element} [props.helper] Helper content to be rendered at the bottom of the card body.
 * @param {(newValue: number) => void} props.onChange Callback called with the new rate once it is changed.
 */
export default function EstimatedShippingRatesCard( {
	audienceCountries,
	value,
	helper,
	onChange,
} ) {
	return (
		<Section.Card>
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Estimated shipping rates',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					<ShippingRateInputControl
						countryOptions={ audienceCountries }
						value={ value }
						onChange={ onChange }
					/>
				</VerticalGapLayout>
				{ helper }
			</Section.Card.Body>
		</Section.Card>
	);
}
