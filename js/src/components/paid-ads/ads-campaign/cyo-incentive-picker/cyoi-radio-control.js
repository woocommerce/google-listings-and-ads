/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';

/**
 * A custom radio control for the CYOI incentive picker, which displays the reward amount in the option label.
 *
 * @typedef {Object} CYOIRadioControlProps
 * @property {string} label The label to display next to the radio button, typically the formatted reward amount.
 * @property {string|number} value The value of the radio button, typically the incentive ID.
 * @property {string|number} selected The currently selected value, used to determine if this radio button is checked.
 * @property {Object} rest Any additional props to pass to the underlying RadioControl component.
 *
 * @param {CYOIRadioControlProps} props The props for the CYOIRadioControl component.
 * @return {JSX.Element} The rendered CYOIRadioControl component.
 */
const CYOIRadioControl = ( { label, value, selected, ...rest } ) => {
	return (
		<div className="gla-cyoi-radio-control__wrapper">
			<RadioControl
				{ ...rest }
				options={ [ { value } ] }
				checked={ String( selected ) === String( value ) }
				aria-label={ sprintf(
					/* translators: %s: incentive amount */
					__(
						'Select incentive amount %s',
						'google-listings-and-ads'
					),
					label
				) }
				hideLabelFromVision
			/>
			<span className="gla-cyoi-incentive-picker__option">{ label }</span>
		</div>
	);
};

export default CYOIRadioControl;
