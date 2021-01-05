/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppInputControl from '../../../../../../components/app-input-control';
import More from './more';
import './index.scss';

const CountriesPriceInput = ( props ) => {
	const { value, onChange } = props;
	const { countries, price } = value;

	const first5countries = countries.slice( 0, 5 ).map( ( c ) => c.label );
	const remainingCount = countries.length - first5countries.length;

	const handleChange = ( v ) => {
		onChange( {
			countries,
			price: v,
		} );
	};

	return (
		<div className="countries-price-input">
			<AppInputControl
				label={ createInterpolateElement(
					__(
						`Shipping rate for <countries /><more />`,
						'google-listings-and-ads'
					),
					{
						countries: (
							<strong>{ first5countries.join( ', ' ) }</strong>
						),
						more: <More count={ remainingCount } />,
					}
				) }
				suffix={ __( 'USD', 'google-listings-and-ads' ) }
				value={ price }
				onChange={ handleChange }
			/>
		</div>
	);
};

export default CountriesPriceInput;
