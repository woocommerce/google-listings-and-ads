/**
 * External dependencies
 */
import GridiconCheckmarkCircle from 'gridicons/dist/checkmark-circle';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a green filled `GridiconCheckmarkCircle` with default size 18.
 *
 * ## Usage
 *
 * ```jsx
 * <SuccessIcon />
 * ```
 *
 * @param {Object} props React props.
 * @param {number} [props.size=18] Icon size.
 */
const SuccessIcon = ( { size = 18 } ) => {
	return (
		<GridiconCheckmarkCircle className="app-success-icon" size={ size } />
	);
};

export default SuccessIcon;
