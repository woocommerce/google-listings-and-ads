/**
 * External dependencies
 */
import classnames from 'classnames';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useAdsCurrency from '~/hooks/useAdsCurrency';
import styles from './cyo-incentive-picker.module.scss';

const CYOIRadioControl = ( {
	label,
	value,
	requirement,
	selected,
	className,
	...rest
} ) => {
	const { formatAmount } = useAdsCurrency();

	return (
		// eslint-disable-next-line jsx-a11y/label-has-associated-control
		<label className={ styles.radioGroup }>
			<RadioControl
				{ ...rest }
				label={ formatAmount( label ) }
				className={ classnames( className ) }
				checked={ String( selected ) === String( value ) }
				options={ [ { value } ] }
				hideLabelFromVision
				help=""
				aria-label={ value }
			/>
			<span className={ classnames( styles.option ) }>
				{ formatAmount( label ) }
			</span>
		</label>
	);
};

export default CYOIRadioControl;
