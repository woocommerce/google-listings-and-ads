/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '../../../../../../hooks/useCountryKeyNameMap';
import AppInputControl from '../../../../../../components/app-input-control';
import More from '../../../components/more';
import useGetAudienceCountries from '../../../hooks/useGetAudienceCountries';
import EditRateButton from './edit-rate-button';
import './index.scss';

const firstN = 5;

const CountriesPriceInput = ( props ) => {
	const { value, onChange } = props;
	const { countries, currency, price } = value;

	const audienceCountries = useGetAudienceCountries();
	const keyNameMap = useCountryKeyNameMap();
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
											{ audienceCountries.length ===
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
