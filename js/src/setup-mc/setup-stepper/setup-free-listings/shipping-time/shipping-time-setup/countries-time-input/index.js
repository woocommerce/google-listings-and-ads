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
import EditTimeButton from './edit-time-button';
import './index.scss';

const CountriesTimeInput = ( props ) => {
	const { value, onChange } = props;
	const { countries, time } = value;

	const first5countries = countries.slice( 0, 5 ).map( ( c ) => c.label );
	const remainingCount = countries.length - first5countries.length;

	const handleChange = ( v ) => {
		onChange( {
			countries,
			time: v,
		} );
	};

	return (
		<div className="gla-countries-time-input">
			<AppInputControl
				label={
					<div className="label">
						<div>
							{ createInterpolateElement(
								__(
									`Shipping time for <countries /><more />`,
									'google-listings-and-ads'
								),
								{
									countries: (
										<strong>
											{ first5countries.join( ', ' ) }
										</strong>
									),
									more: <More count={ remainingCount } />,
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
