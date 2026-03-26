/**
 * External dependencies
 */
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useAdsCurrency from '~/hooks/useAdsCurrency';

/**
 * A custom radio control for the CYOI incentive picker, which displays the reward amount in the option label.
 *
 * @typedef {Object} CYOIRadioControlProps
 * @property {number} amount The reward amount to display in the label.
 * @property {string} value The value of the radio option.
 * @return {JSX.Element} The rendered radio control component.
 */
const CYOIRadioControl = ( { amount, value } ) => {
	const { formatAmount } = useAdsCurrency();

	return (
		// eslint-disable-next-line jsx-a11y/label-has-associated-control
		<label className="gla-cyoi-radio-control__wrapper">
			<RadioControl
				label={ formatAmount( amount ) }
				options={ [ { value } ] }
				aria-label={ value }
				hideLabelFromVision
			/>
			<span className="gla-cyoi-incentive-picker__option">
				{ formatAmount( amount ) }
			</span>
		</label>
	);
};

export default CYOIRadioControl;
