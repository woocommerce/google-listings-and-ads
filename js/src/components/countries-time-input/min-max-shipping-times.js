/**
 * External dependencies
 */
import { Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TimeStepper from './time-stepper';

const MinMaxShippingTimes = ( {
	handleBlur,
	handleIncrement,
	time,
	maxTime,
} ) => {
	return (
		<Flex gap="4" justify="space-between">
			<FlexItem>
				<div className="gla-countries-time-input">
					<TimeStepper
						field="time"
						handleBlur={ handleBlur }
						handleIncrement={ handleIncrement }
						time={ time }
					/>
				</div>
			</FlexItem>
			<FlexItem>
				<span>{ __( 'to', 'google-listings-and-ads' ) }</span>
			</FlexItem>
			<FlexItem>
				<div className="gla-countries-time-input">
					<TimeStepper
						field="maxTime"
						handleBlur={ handleBlur }
						handleIncrement={ handleIncrement }
						time={ maxTime }
					/>
				</div>
			</FlexItem>
		</Flex>
	);
};

export default MinMaxShippingTimes;
