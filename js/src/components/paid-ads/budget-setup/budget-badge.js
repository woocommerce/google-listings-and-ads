/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import DeltaValue from '~/components/delta-value';
import round from '~/utils/round';
import styles from './budget-setup.module.scss';

export default function BudgetBadge( { amount } ) {
	const isNegative = round( amount ) < 0;
	const isPositive = round( amount ) > 0;

	return (
		<Badge
			className={ classnames( styles.budgetBadge, {
				[ styles.budgetBadgeNegative ]: isNegative,
				[ styles.budgetBadgePositive ]: isPositive,
				[ styles.budgetBadgeNeutral ]: ! isNegative && ! isPositive,
			} ) }
		>
			<DeltaValue amount={ round( amount ) } suffix="%" />
		</Badge>
	);
}
