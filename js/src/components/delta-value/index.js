/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Displays a formatted delta value with a positive or negative sign
 * and an optional suffix. The component also applies appropriate CSS classes based on the
 * sign of the value.
 *
 * @param {Object} props - The component props.
 * @param {number} props.amount - The numeric value to be displayed. Determines if the value is positive, negative, or zero.
 * @param {string} [props.suffix=''] - An optional string to append to the formatted value.
 *
 * @return {JSX.Element} A span element containing the formatted delta value with appropriate CSS classes applied.
 */
const DeltaValue = ( { amount = 0, suffix = '' } ) => {
	let value = amount;
	if ( isNaN( amount ) ) {
		value = 0;
	}

	const isPositive = value > 0;

	let formattedValue = `${ parseInt( value, 10 ) }${ String( suffix ) }`;
	if ( isPositive ) {
		formattedValue = `+${ formattedValue }`;
	}

	const isNegative = value < 0;
	return (
		<span
			className={ classnames( 'gla-delta-value', {
				'gla-delta-value--negative': isNegative,
				'gla-delta-value--positive': isPositive,
			} ) }
		>
			{ formattedValue }
		</span>
	);
};

export default DeltaValue;
