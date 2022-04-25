/**
 * Internal dependencies
 */
import AppInputPriceControl from '.~/components/app-input-price-control';
import MinimumOrderInputControlLabelText from './minimum-order-input-control-label-text';
import './minimum-order-input-control.scss';

/**
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Input control to edit a minimum order group.
 *
 * The input control label contains a placeholder area for `button`, after the label text.
 * This is meant to display an "Edit" button.
 *
 * @param {Object} props
 * @param {JSX.Element} props.labelButton Button to be displayed after the label text.
 * @param {MinimumOrderGroup} props.value Minimum order group value.
 * @param {(newGroup: MinimumOrderGroup) => void} props.onChange Called when minimum order group changes.
 */
const MinimumOrderInputControl = ( props ) => {
	const { labelButton, value, onChange } = props;
	const { countries, threshold, currency } = value;

	const handleBlur = ( event, numberValue ) => {
		if ( numberValue === value.threshold ) {
			return;
		}

		onChange( {
			countries,
			threshold: numberValue > 0 ? numberValue : undefined,
			currency,
		} );
	};

	return (
		<AppInputPriceControl
			className="gla-minimum-order-input-control"
			label={
				<div className="label">
					<MinimumOrderInputControlLabelText
						countries={ countries }
					/>
					{ labelButton }
				</div>
			}
			suffix={ currency }
			value={ threshold }
			onBlur={ handleBlur }
		/>
	);
};

export default MinimumOrderInputControl;
