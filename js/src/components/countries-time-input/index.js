/**
 * External dependencies
 */
import { Flex, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormInputProps } from '~/components/adaptive-form';
import MinMaxShippingTimes from './min-max-shipping-times';
import './index.scss';

/**
 * Input component for the estimated flat shipping time (min and max days) applied to all target countries.
 */
const CountriesTimeInput = () => {
	const { value: time, onChange: onMinTimeChange } =
		useAdaptiveFormInputProps( 'flat_shipping_min_time' );
	const { value: maxTime, onChange: onMaxTimeChange } =
		useAdaptiveFormInputProps( 'flat_shipping_max_time' );

	const handleBlur = ( numberValue, field ) => {
		if ( field === 'time' ) {
			if ( time !== numberValue ) {
				onMinTimeChange( numberValue );
			}
		} else if ( field === 'maxTime' && maxTime !== numberValue ) {
			onMaxTimeChange( numberValue );
		}
	};

	const handleIncrement = ( numberValue, field ) => {
		if ( field === 'time' ) {
			onMinTimeChange( numberValue );
		} else if ( field === 'maxTime' ) {
			onMaxTimeChange( numberValue );
		}
	};

	return (
		<Flex className="gla-countries-time-input-container">
			<FlexBlock>
				<MinMaxShippingTimes
					handleBlur={ handleBlur }
					handleIncrement={ handleIncrement }
					maxTime={ maxTime }
					time={ time }
				/>
			</FlexBlock>
		</Flex>
	);
};

export default CountriesTimeInput;
