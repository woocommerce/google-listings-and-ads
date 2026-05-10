/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import './minimum-order-card.scss';
import FreeShippingThresholdControl from './free-shipping-threshold-control';

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
						value={ value }
						onChange={ onChange }
					/>
				</VerticalGapLayout>
				{ helper }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default MinimumOrderCard;
