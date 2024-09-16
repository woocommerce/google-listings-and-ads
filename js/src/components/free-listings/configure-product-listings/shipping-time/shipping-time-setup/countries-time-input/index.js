/**
 * External dependencies
 */
import { Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import ShippingTimeInputControlLabelText from '.~/components/shipping-time-input-control-label-text';
import EditTimeButton from './edit-time-button';
import Stepper from '../time-stepper';
import './index.scss';

/**
 * Input control to edit a shipping time.
 * Consists of a simple input field to adjust the time
 * and with a modal with a more advanced form to select countries.
 *
 * @param {Object} props
 * @param {AggregatedShippingTime} props.value Aggregate, rat: Array object to be used as the initial value.
 * @param {Array<CountryCode>} props.audienceCountries List of all audience countries.
 * @param {(newTime: AggregatedShippingTime, deletedCountries: Array<CountryCode>|undefined) => void} props.onChange Called when time changes.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 */
const CountriesTimeInput = ( {
	value,
	audienceCountries,
	onChange,
	onDelete,
} ) => {
	const { countries, time, maxTime } = value;

	if ( ! audienceCountries ) {
		return <AppSpinner />;
	}

	/**
	 *
	 * @param {Object} e The event object
	 * @param {number} numberValue The string value of the input field converted to a number
	 * @param {string} field The field name: time or maxTime
	 */
	const handleBlur = ( e, numberValue, field ) => {
		if ( value[ field ] === numberValue ) {
			return;
		}

		onChange( {
			...value,
			[ field ]: numberValue,
		} );
	};

	/**
	 *
	 * @param {number} numberValue The string value of the input field converted to a number
	 * @param {string} field The field name: time or maxTime
	 */
	const handleIncrement = ( numberValue, field ) => {
		onChange( {
			...value,
			[ field ]: numberValue,
		} );
	};

	return (
		<Flex direction="column" className="gla-countries-time-input-container">
			<FlexItem>
				<div className="label">
					<ShippingTimeInputControlLabelText
						countries={ countries }
					/>
					<EditTimeButton
						audienceCountries={ audienceCountries }
						onChange={ onChange }
						onDelete={ onDelete }
						time={ value }
					/>
				</div>
			</FlexItem>

			<FlexItem>
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
			</FlexItem>
		</Flex>
	);
};

export default CountriesTimeInput;

/**
 * @typedef { import(".~/data/actions").AggregatedShippingTime } AggregatedShippingTime
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
