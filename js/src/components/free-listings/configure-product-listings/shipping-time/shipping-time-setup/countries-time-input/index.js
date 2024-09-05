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

	const handleBlur = ( e, numberValue ) => {
		if ( time === numberValue ) {
			return;
		}

		onChange( {
			countries,
			time: numberValue,
		} );
	};

	return (
		<Flex direction="column" className="gla-countries-time-input-container">
			<div className="label">
				<ShippingTimeInputControlLabelText countries={ countries } />
				<EditTimeButton
					audienceCountries={ audienceCountries }
					onChange={ onChange }
					onDelete={ onDelete }
					time={ value }
				/>
			</div>
			<FlexItem>
				<Flex justify="space-evenly" gap="4">
					<FlexItem>
						<div className="gla-countries-time-input">
							<Stepper
								onChange={ onChange }
								onDelete={ onDelete }
								handleBlur={ handleBlur }
								countries={ countries }
								time={ time }
							/>
						</div>
					</FlexItem>
					<FlexItem>
						<span>{ __( 'to', 'google-listings-and-ads' ) }</span>
					</FlexItem>
					<FlexItem>
						<div className="gla-countries-time-input">
							<Stepper
								onChange={ onChange }
								onDelete={ onDelete }
								handleBlur={ handleBlur }
								countries={ countries }
								time={ maxTime }
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
