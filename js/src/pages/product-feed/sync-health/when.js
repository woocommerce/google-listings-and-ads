/**
 * Internal dependencies
 */
import { formatDateTime, timeAgo } from './utils';

/**
 * Relative time, e.g. "5 minutes ago", with the absolute date and time on hover.
 *
 * @param {Object} props
 * @param {number|null} props.timestamp Unix timestamp in seconds.
 * @param {string} [props.fallback] Shown when there is no timestamp.
 */
const When = ( { timestamp, fallback } ) => (
	<time title={ formatDateTime( timestamp ) }>
		{ timeAgo( timestamp, fallback ) }
	</time>
);

export default When;
