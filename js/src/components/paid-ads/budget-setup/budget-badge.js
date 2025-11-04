/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import DeltaValue from '~/components/delta-value';
import round from '~/utils/round';
import styles from './budget-setup.module.scss';

/**
 * Renders a badge displaying the percentage change in budget.
 *
 * @param {Object} props The component props.
 * @param {number} props.amount The amount to display in the badge.
 * @return {JSX.Element} The rendered badge component.
 */
export default function BudgetBadge( { amount } ) {
	const intent = round( amount ) > 0 ? 'success' : 'error';

	return (
		<Badge className={ styles.budgetBadge } intent={ intent }>
			<DeltaValue amount={ round( amount ) } suffix="%" />
		</Badge>
	);
}
