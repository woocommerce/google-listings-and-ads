/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppInputNumberControl from '.~/components/app-input-number-control';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import EditTimeButton from './edit-time-button';
import './index.scss';
import CountryNames from '.~/components/free-listings/configure-product-listings/country-names';

const CountriesTimeInput = ( props ) => {
	const { value, onChange } = props;
	const { countries, time } = value;
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	const handleChange = ( v ) => {
		onChange( {
			countries,
			time: v,
		} );
	};

	return (
		<div className="gla-countries-time-input">
			<AppInputNumberControl
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
											total={
												selectedCountryCodes.length
											}
										/>
									),
								}
							) }
						</div>
						<EditTimeButton time={ value } />
					</div>
				}
				suffix={ __( 'days', 'google-listings-and-ads' ) }
				value={ time }
				onChange={ handleChange }
			/>
		</div>
	);
};

export default CountriesTimeInput;
