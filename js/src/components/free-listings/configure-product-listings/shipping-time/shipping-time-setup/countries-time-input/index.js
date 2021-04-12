/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppInputControl from '.~/components/app-input-control';
import AppSpinner from '.~/components/app-spinner';
import EditTimeButton from './edit-time-button';
import './index.scss';
import CountryNames from '.~/components/free-listings/configure-product-listings/country-names';

/**
 * Input control to edit a shipping time.
 * Consists of a simple input field to adjust the time
 * and with a modal with a more advanced form to select countries.
 *
 * @param {Object} props
 * @param {AggregatedShippingTime} props.value Aggregate, rat: Array object to be used as the initial value.
 * @param {Array<CountryCode>} props.audienceCountries List of all audience countries.
 * @param {number} props.totalCountyCount Number of all anticipated countries.
 * @param {(newTime: AggregatedShippingTime, deletedCountries: Array<CountryCode>|undefined) => void} props.onChange Called when time changes.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 */
const CountriesTimeInput = ( {
	value,
	audienceCountries,
	totalCountyCount,
	onChange,
	onDelete,
} ) => {
	const { countries, time } = value;

	if ( ! audienceCountries ) {
		return <AppSpinner />;
	}

	const handleBlur = ( e ) => {
		const { value: nextTime } = e.target;

		if ( nextTime === time ) {
			return;
		}

		if ( nextTime === '' ) {
			onDelete( countries );
		} else {
			onChange( {
				countries,
				time: nextTime,
			} );
		}
	};

	return (
		<div className="gla-countries-time-input">
			<AppInputControl
				label={
					<div className="label">
						<div>
							{ createInterpolateElement(
								__(
									`Shipping time for <countries />`,
									'google-listings-and-ads'
								),
								{
									countries: (
										<CountryNames
											countries={ countries }
											total={ totalCountyCount }
										/>
									),
								}
							) }
						</div>
						<EditTimeButton
							audienceCountries={ audienceCountries }
							onChange={ onChange }
							onDelete={ onDelete }
							time={ value }
						/>
					</div>
				}
				suffix={ __( 'days', 'google-listings-and-ads' ) }
				value={ time }
				onBlur={ handleBlur }
			/>
		</div>
	);
};

export default CountriesTimeInput;

/**
 * @typedef { import(".~/data/actions").AggregatedShippingTime } AggregatedShippingTime
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
