/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { plus, reset } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppInputNumberControl from '.~/components/app-input-number-control';
import './index.scss';

const Stepper = ( {
	step = 1,
	min = 0,
	max = 250, // Google's UI in the MC shows a maximum limit of 250 days, though the API doesn’t appear to have any such restriction.
	time,
	handleBlur,
	handleIncrement,
	field = 'time',
} ) => {
	const [ value, setValue ] = useState( time );
	const incrementTimeoutRef = useRef( null );
	const debounceTime = 600;

	useEffect( () => {
		// If the time is 0, we want to display an empty string to show the "Same Day" delivery placeholder.
		setValue( time === 0 ? '' : time );
	}, [ time ] );

	function onIncrement( increment ) {
		const newValue = parseFloat( value || 0 ) + increment;

		if ( newValue >= min && newValue <= max ) {
			setValue( newValue );

			// Clear previous timeout
			if ( incrementTimeoutRef.current ) {
				clearTimeout( incrementTimeoutRef.current );
			}

			incrementTimeoutRef.current = setTimeout( () => {
				handleIncrement( newValue, field );
			}, debounceTime );
		}
	}

	const onBlur = ( e, numberValue ) => {
		if ( numberValue >= min && numberValue <= max ) {
			handleBlur( numberValue, field );
		}
	};

	return (
		<AppInputNumberControl
			step={ step }
			placeholder={
				// When onboarding, the time is null, and we don't want to show the placeholder because we need the user to enter a value for us to store.
				time === null ? '' : __( 'Same Day', 'google-listings-and-ads' )
			}
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
							size="small"
							onMouseDown={ () => onIncrement( step ) }
							aria-label={ __(
								'Increment',
								'google-listings-and-ads'
							) }
							tabIndex={ -1 }
						/>
						<Button
							icon={ reset }
							className="woocommerce-number-control__decrement"
							size="small"
							onMouseDown={ () => onIncrement( -step ) }
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
			onBlur={ onBlur }
			className="gla-countries-time-stepper"
		/>
	);
};

export default Stepper;
