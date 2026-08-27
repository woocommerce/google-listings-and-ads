/**
 * External dependencies
 */
import classnames from 'classnames';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import styles from './budget-setup.module.scss';

/**
 * Renders a radio control dedicated to the `BudgetSetup` component.
 *
 * @param {Object} props React props.
 * @param {JSX.Element} props.label The label for the radio control.
 * @param {string} props.value The value of the radio control.
 * @param {string} props.selected The current selected value.
 * @param {string} [props.className] Additional CSS class name to be appended to RadioControl.
 * @param {Object} props.rest Additional props to pass to the RadioControl.
 */
export default function BudgetRadioControl( {
	label,
	value,
	selected,
	className,
	...rest
} ) {
	return (
		// eslint-disable-next-line jsx-a11y/label-has-associated-control
		<label className={ styles.radioGroup }>
			<RadioControl
				{ ...rest }
				aria-label={ value }
				checked={ selected === value }
				className={ classnames( styles.radio, className ) }
				help=""
				options={ [ { value } ] }
				hideLabelFromVision
			/>
			<span
				className={ classnames(
					styles.option,
					value === 'custom' && styles.customOption
				) }
			>
				{ label }
			</span>
		</label>
	);
}
