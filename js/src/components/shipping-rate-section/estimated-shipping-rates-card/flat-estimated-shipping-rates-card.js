/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';
import Section from '~/components/section';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import AppInputPriceControl from '~/components/app-input-price-control';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import ShippingRateInputControlLabelText from './shipping-rate-input-control-label-text';
import './flat-estimated-shipping-rates-card.scss';

/**
 * Simplified estimated shipping rates card for flat-rate shipping.
 * All audience countries share a single rate.
 * Reads `flat_shipping_rate` and `audienceCountries` from the adaptive form context.
 *
 * @param {Object} props
 * @param {JSX.Element} [props.helper] Helper content rendered at the bottom of the card body.
 */
const FlatEstimatedShippingRatesCard = ( { helper } ) => {
	const { adapter: { audienceCountries, renderRequestedValidation } } =
		useAdaptiveFormContext();
	const { value, onChange } =
		useAdaptiveFormInputProps( 'flat_shipping_rate' );
	const { code: currencyCode } = useStoreCurrency();

	const handleBlur = ( event, numberValue ) => {
		if ( value === numberValue ) {
			return;
		}
		onChange( numberValue );
	};

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
					<div className="gla-shipping-rate-input-control">
						<AppInputPriceControl
							label={
								<ShippingRateInputControlLabelText
									countries={ audienceCountries }
								/>
							}
							suffix={ currencyCode }
							value={ value }
							onBlur={ handleBlur }
							hideLabelFromVision
						/>
						{ renderRequestedValidation( 'flat_shipping_rate' ) }
					</div>

					{ value === 0 && (
						<div className="gla-input-pill-div">
							<Pill>
								{ __(
									'Free shipping for all orders',
									'google-listings-and-ads'
								) }
							</Pill>
						</div>
					) }
				</VerticalGapLayout>
				{ helper }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default FlatEstimatedShippingRatesCard;
