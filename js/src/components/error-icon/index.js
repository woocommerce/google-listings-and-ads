/**
 * External dependencies
 */
import GridiconNotice from 'gridicons/dist/notice';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a red filled `GridiconNotice` with default size 18.
 *
 * ## Usage
 *
 * ```jsx
 * <ErrorIcon />
 * ```
 *
 * @param {Object} props React props.
 * @param {number} [props.size=18] Icon size.
 */
const ErrorIcon = ( { size = 18 } ) => {
	return <GridiconNotice className="gla-error-icon" size={ size } />;
};

export default ErrorIcon;
