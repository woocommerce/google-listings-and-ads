/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import styles from './budget-setup.module.scss';

/**
 * Renders a label for daily budget amount input field.
 *
 * @param {Object} props Component props.
 * @param {string|number} props.amount The daily budget amount to display.
 * @return {JSX.Element} The rendered label component.
 */
export default function DailyBudgetLabel( { amount } ) {
	return (
		<>
			{ amount }
			<span className={ styles.dayUnit }>
				/{ __( 'day', 'google-listings-and-ads' ) }
			</span>
		</>
	);
}
