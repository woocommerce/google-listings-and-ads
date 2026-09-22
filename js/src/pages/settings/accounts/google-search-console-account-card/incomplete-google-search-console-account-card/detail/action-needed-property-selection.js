/**
 * Internal dependencies
 */
import PropertySelection from './property-selection';

/**
 * Renders the action-needed step's detail: {@see ./property-selection.js}'s selector and
 * create-new action, with copy explaining that the previously connected property is no longer
 * usable rather than the initial multi-match copy. Unlike the initial setup step, the create
 * action stays available even with zero other candidates.
 *
 * @return {JSX.Element} The detail.
 */
export default function ActionNeededPropertySelection() {
	return <PropertySelection actionNeeded />;
}
