/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import AppInputControl from '.~/components/app-input-control';
import More from '.~/components/edit-program/free-listings/setup-free-listings/components/more';
import EditRateButton from './edit-rate-button';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import './index.scss';

/**
 * The limit of the number of countries to show.
 */
const firstN = 5;

const CountriesPriceInput = ( props ) => {
	const { value, onChange } = props;
	const { countries, currency, price } = value;

	const keyNameMap = useCountryKeyNameMap();
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	const firstCountryNames = countries
		.slice( 0, firstN )
		.map( ( c ) => keyNameMap[ c ] );
	const remainingCount = countries.length - firstCountryNames.length;

	const handleChange = ( v ) => {
		onChange( {
			countries,
			currency,
			price: v,
		} );
	};

	return (
		<div className="gla-countries-price-input">
			<AppInputControl
				label={
					<div className="label">
						<div>
							{ createInterpolateElement(
								__(
									`Shipping rate for <countries /><more />`,
									'google-listings-and-ads'
								),
								{
									countries: (
										<strong>
											{ selectedCountryCodes.length ===
											countries.length
												? __(
														`all countries`,
														'google-listings-and-ads'
												  )
												: firstCountryNames.join(
														', '
												  ) }
										</strong>
									),
									more: <More count={ remainingCount } />,
								}
							) }
						</div>
						<EditRateButton rate={ value } />
					</div>
				}
				suffix={ currency }
				value={ price }
				onChange={ handleChange }
			/>
		</div>
	);
};

export default CountriesPriceInput;
