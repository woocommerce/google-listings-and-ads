/**
 * External dependencies
 */
import GridiconNoticeOutline from 'gridicons/dist/notice-outline';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Outline warning icon
 *
 * @param {Object} props React props.
 * @param {number} [props.size=18] Icon size.
 */
const WarningIcon = ( { size = 18 } ) => {
	return <GridiconNoticeOutline className="gla-warning-icon" size={ size } />;
};

export default WarningIcon;
