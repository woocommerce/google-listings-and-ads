/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';
import Section from '~/components/section';
import ShippingTimeInputControlLabelText from '~/components/shipping-time-input-control-label-text';
import MinMaxShippingTimes from './min-max-shipping-times';

/**
 * Simplified estimated shipping times card for flat-rate shipping.
 * All audience countries share a single min/max time.
 * Reads `flat_shipping_min_time`, `flat_shipping_max_time`, and `audienceCountries` from the adaptive form context.
 */
const FlatEstimatedShippingTimesCard = () => {
	const {
		adapter: { audienceCountries, renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { value: minTime, onChange: onMinTimeChange } =
		useAdaptiveFormInputProps( 'flat_shipping_min_time' );
	const { value: maxTime, onChange: onMaxTimeChange } =
		useAdaptiveFormInputProps( 'flat_shipping_max_time' );

	const handleBlur = ( numberValue, field ) => {
		if ( field === 'time' ) {
			if ( minTime !== numberValue ) {
				onMinTimeChange( numberValue );
			}
		} else if ( maxTime !== numberValue ) {
			onMaxTimeChange( numberValue );
		}
	};

	const handleIncrement = ( numberValue, field ) => {
		if ( field === 'time' ) {
			onMinTimeChange( numberValue );
		} else {
			onMaxTimeChange( numberValue );
		}
	};

	return (
		<Section.Card>
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Estimated shipping times',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<div className="gla-shipping-rate-input-control">
					<ShippingTimeInputControlLabelText
						countries={ audienceCountries }
					/>
					<MinMaxShippingTimes
						time={ minTime }
						maxTime={ maxTime }
						handleBlur={ handleBlur }
						handleIncrement={ handleIncrement }
					/>
					{ renderRequestedValidation( 'flat_shipping_times' ) }
				</div>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default FlatEstimatedShippingTimesCard;
