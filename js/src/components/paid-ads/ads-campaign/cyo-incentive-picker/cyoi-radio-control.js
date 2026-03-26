/**
 * External dependencies
 */
import { RadioControl } from '@wordpress/components';

/**
 * A custom radio control for the CYOI incentive picker, which displays the reward amount in the option label.
 *
 * @typedef {Object} CYOIRadioControlProps
 * @property {number} amount The reward amount to display in the label.
 * @property {string} value The value of the radio option.
 * @return {JSX.Element} The rendered radio control component.
 */
const CYOIRadioControl = ( { label, value, selected, ...rest } ) => {
	return (
		// eslint-disable-next-line jsx-a11y/label-has-associated-control
		<label className="gla-cyoi-radio-control__wrapper">
			<RadioControl
				{ ...rest }
				options={ [ { value } ] }
				checked={ String( selected ) === String( value ) }
				hideLabelFromVision
			/>
			<span className="gla-cyoi-incentive-picker__option">{ label }</span>
		</label>
	);
};

export default CYOIRadioControl;
