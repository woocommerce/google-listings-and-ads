/**
 * External dependencies
 */
import { default as IconSync } from 'gridicons/dist/sync';
import classnames from 'classnames';

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
 * @param {string} [props.className] Icon custom class name
 */
const SyncIcon = ( { size = 18, className } ) => {
	return (
		<IconSync
			className={ classnames( 'gla-sync-icon', className ) }
			size={ size }
		/>
	);
};

export default SyncIcon;
