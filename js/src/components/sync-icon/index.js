/**
 * External dependencies
 */
import { default as IconSync } from 'gridicons/dist/sync';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a yellow filled `SyncIcon` with default size 18.
 *
 * ## Usage
 *
 * ```jsx
 * <SyncIcon />
 * ```
 *
 * @param {Object} props React props.
 * @param {number} [props.size=18] Icon size.
 */
const SyncIcon = ( { size = 18 } ) => {
	return <IconSync className="gla-sync-icon" size={ size } />;
};

export default SyncIcon;
