/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '~/components/app-input-price-control';
import OfferFreeShippingCheckbox from '~/components/order-value-condition-section/offer-free-shipping-checkbox';
import VerticalGapLayout from '~/components/vertical-gap-layout';

const MinimumOrderInputs = ( {
	offerFreeShippingInputProps,
	offerFreeShipping,
	currency,
	threshold,
	onCostBlur,
} ) => {
	return (
		<VerticalGapLayout size="large" className="gla-minimum-order-inputs">
			<OfferFreeShippingCheckbox { ...offerFreeShippingInputProps } />
			{ offerFreeShipping && (
				<AppInputPriceControl
					label={ __( 'Cost', 'google-listings-and-ads' ) }
					suffix={ currency }
					value={ threshold }
					onBlur={ onCostBlur }
				/>
			) }
		</VerticalGapLayout>
	);
};

export default MinimumOrderInputs;
