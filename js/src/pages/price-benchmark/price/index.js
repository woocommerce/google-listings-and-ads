/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import useAdsCurrency from '~/hooks/useAdsCurrency';
import './index.scss';

/**
 * Price component to display a formatted price value with optional highlighting.
 *
 * @param {Object} props - The component props.
 * @param {number} props.amount - The price value to be formatted and displayed.
 * @param {boolean} [props.highlight=false] - Whether to apply a highlight style to the price.
 * @return {JSX.Element} The rendered price component.
 */
const Price = ( { amount, highlight = false } ) => {
	const { formatAmount } = useAdsCurrency();
	const valueToFormat = isNaN( amount ) ? 0 : amount;

	return (
		<span
			className={ classnames( 'gla-price-benchmark-table__price', {
				'gla-price-benchmark-table__price--highlight': highlight,
			} ) }
		>
			{ formatAmount( valueToFormat ) }
		</span>
	);
};

export default Price;
