/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppInputControl from '../../../../../../components/app-input-control';
import EditRateButton from './edit-rate-button';
import More from './more';
import './index.scss';
import useGetAudienceCountries from '../../../hooks/useGetAudienceCountries';

const CountriesPriceInput = ( props ) => {
	const { value, onChange } = props;
	const { countries, price } = value;

	const audienceCountries = useGetAudienceCountries();
	const first5countries = countries.slice( 0, 5 ).map( ( c ) => c.label );
	const remainingCount = countries.length - first5countries.length;

	const handleChange = ( v ) => {
		onChange( {
			countries,
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
												: first5countries.join( ', ' ) }
										</strong>
									),
									more: <More count={ remainingCount } />,
								}
							) }
						</div>
						<EditRateButton rate={ value } />
					</div>
				}
				suffix={ __( 'USD', 'google-listings-and-ads' ) }
				value={ price }
				onChange={ handleChange }
			/>
		</div>
	);
};

export default CountriesPriceInput;
