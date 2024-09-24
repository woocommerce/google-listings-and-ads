/**
 * External dependencies
 */
import { Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Stepper from '../time-stepper';

const MinMaxShippingTimes = ( {
	handleBlur,
	handleIncrement,
	time,
	maxTime,
} ) => {
	return (
		<Flex justify="space-between" gap="4">
			<FlexItem>
				<div className="gla-countries-time-input">
					<Stepper
						handleBlur={ handleBlur }
						time={ time }
						handleIncrement={ handleIncrement }
						field="time"
					/>
				</div>
			</FlexItem>
			<FlexItem>
				<span>{ __( 'to', 'google-listings-and-ads' ) }</span>
			</FlexItem>
			<FlexItem>
				<div className="gla-countries-time-input">
					<Stepper
						handleBlur={ handleBlur }
						handleIncrement={ handleIncrement }
						time={ maxTime }
						field="maxTime"
					/>
				</div>
			</FlexItem>
		</Flex>
	);
};

export default MinMaxShippingTimes;
