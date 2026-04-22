/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * @typedef { import("~/data/actions").ShippingRate } ShippingRate
 */

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppInputPriceControl from '~/components/app-input-price-control';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';
import OfferFreeShippingCheckbox from '~/components/order-value-condition-section/offer-free-shipping-checkbox';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import './minimum-order-card.scss';

/**
 * Renders a Card UI to set a single free shipping threshold applied to all countries.
 *
 * @param {Object} props React props.
 * @param {Array<ShippingRate>} [props.value=[]] Array of shipping rates; the threshold is read from the first non-free rate and written back to all rates.
 * @param {JSX.Element} [props.helper] Helper content to be rendered at the bottom of the card body.
 * @param {(nextValue: Array<ShippingRate>) => void} props.onChange Callback called with the updated rates once the threshold changes.
 */
const MinimumOrderCard = ( { value = [], helper, onChange } ) => {
	const offerFreeShippingInputProps = useAdaptiveFormInputProps(
		'offer_free_shipping'
	);
	const { values } = useAdaptiveFormContext();

	const nonFreeRates = value.filter( isNonFreeShippingRate );
	const threshold = nonFreeRates[ 0 ]?.options?.free_shipping_threshold;
	const currency = value[ 0 ]?.currency;

	const handleBlur = ( _event, numberValue ) => {
		if ( numberValue === threshold ) {
			return;
		}
		onChange(
			value.map( ( rate ) => ( {
				...rate,
				options: {
					...rate.options,
					free_shipping_threshold:
						numberValue > 0 ? numberValue : undefined,
				},
			} ) )
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
					<OfferFreeShippingCheckbox
						{ ...offerFreeShippingInputProps }
					/>
					{ values.offer_free_shipping && (
						<AppInputPriceControl
							label={ __( 'Cost', 'google-listings-and-ads' ) }
							suffix={ currency }
							value={ threshold }
							onBlur={ handleBlur }
						/>
					) }
				</VerticalGapLayout>
				{ helper }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default MinimumOrderCard;
