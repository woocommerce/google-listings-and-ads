/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import FreeShippingThresholdControl from '~/components/free-shipping-threshold-control';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import './minimum-order-card.scss';

/**
 * @typedef { import("~/data/actions").ShippingRate } ShippingRate
 */

/**
 * Renders a Card UI to set a single free shipping threshold applied to all countries.
 *
 * @param {Object} props React props.
 * @param {Array<ShippingRate>} [props.value=[]] Array of shipping rates; the threshold is read from the first non-free rate and written back to all rates.
 * @param {JSX.Element} [props.helper] Helper content to be rendered at the bottom of the card body.
 * @param {(nextValue: Array<ShippingRate>) => void} props.onChange Callback called with the updated rates once the threshold changes.
 */
const MinimumOrderCard = ( { value = [], helper, onChange } ) => {
	const nonFreeRates = value.filter( isNonFreeShippingRate );
	const threshold = nonFreeRates[ 0 ]?.options?.free_shipping_threshold;
	const currency = value[ 0 ]?.currency;

	const handleChange = ( numberValue ) => {
		onChange(
			value.map( ( rate ) => {
				if ( ! isNonFreeShippingRate( rate ) ) {
					return rate;
				}

				return {
					...rate,
					options: {
						...rate.options,
						free_shipping_threshold:
							numberValue > 0 ? numberValue : undefined,
					},
				};
			} )
		);
	};

	return (
		<Section.Card className="gla-minimum-order-card">
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Only select if applicable',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					<FreeShippingThresholdControl
						onChange={ handleChange }
						threshold={ threshold }
						currency={ currency }
					/>
				</VerticalGapLayout>
				{ helper }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default MinimumOrderCard;
