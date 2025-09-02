/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import DeltaValue from '~/components/delta-value';
import round from '~/utils/round';
import styles from './budget-setup.module.scss';

export default function BudgetBadge( { amount } ) {
	return (
		<Badge className={ styles.budgetBadge }>
			<DeltaValue
				amount={ round( amount ) }
				prefix="+"
				suffix="%"
				decimalPlaces={ 0 }
			/>
		</Badge>
	);
}
