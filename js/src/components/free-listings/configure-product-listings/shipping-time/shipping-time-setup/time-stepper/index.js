/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { plus, reset } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppInputNumberControl from '.~/components/app-input-number-control';
import { FREE_LISTINGS_SAME_DAY_DELIVERY_STRING } from '.~/constants';
import './index.scss';

const Stepper = ( {
	step = 1,
	min = 0,
	max = Infinity,
	onChange,
	value: savedValue,
	handleBlur,
} ) => {
	const { countries, time } = savedValue;

	const [ value, setValue ] = useState(
		FREE_LISTINGS_SAME_DAY_DELIVERY_STRING
	);

	useEffect( () => {
		setValue( time === 0 ? FREE_LISTINGS_SAME_DAY_DELIVERY_STRING : time );
	}, [ time ] );

	function handleIncrement( thisStep = step ) {
		const newValue = parseFloat( value || '0' ) + thisStep;

		if ( newValue >= min && newValue <= max ) {
			const newValueString =
				newValue === 0
					? FREE_LISTINGS_SAME_DAY_DELIVERY_STRING
					: String( newValue );
			onChange( {
				countries,
				time: newValueString,
			} );

			setValue( newValueString );
		}
	}

	return (
		<AppInputNumberControl
			step={ step }
			placeholder={ 'Same day' }
			suffix={
				<>
					{ parseInt( value, 10 ) >= 1 && (
						<span className="gla-countries-time-suffix">
							{ _n(
								'day',
								'days',
								parseInt( value, 10 ),
								'google-listings-and-ads'
							) }
						</span>
					) }

					<>
						<Button
							className="woocommerce-number-control__increment"
							icon={ plus }
							isSmall
							onMouseDown={ () => handleIncrement( step ) }
							aria-label={ __(
								'Increment',
								'google-listings-and-ads'
							) }
							tabIndex={ -1 }
						/>
						<Button
							icon={ reset }
							className="woocommerce-number-control__decrement"
							isSmall
							onMouseDown={ () => handleIncrement( -step ) }
							aria-label={ __(
								'Decrement',
								'google-listings-and-ads'
							) }
							tabIndex={ -1 }
						/>
					</>
				</>
			}
			value={ value }
			onBlur={ handleBlur }
			className="gla-countries-time-stepper"
		/>
	);
};

export default Stepper;
