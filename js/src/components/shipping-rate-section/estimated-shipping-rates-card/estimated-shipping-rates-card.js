/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import ShippingRateInputControl from '~/components/shipping-rate-input-control';
import ShippingRateInputControlLabelText from './shipping-rate-input-control-label-text';

/**
 * @typedef { import("~/data/actions").CountryCode } CountryCode
 */

/**
 * The "Estimated shipping rates" card with a single flat rate input applied to all audience countries.
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
						label={
							<ShippingRateInputControlLabelText
								countries={ audienceCountries }
							/>
						}
						value={ value }
						onChange={ onChange }
					/>
				</VerticalGapLayout>
				{ helper }
			</Section.Card.Body>
		</Section.Card>
	);
}
