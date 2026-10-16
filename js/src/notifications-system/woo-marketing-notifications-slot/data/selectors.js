/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * @typedef {Object} Notification
 * @property {string} id Unique identifier.
 * @property {number} triggered_at Unix timestamp when notification was triggered.
 * @property {Function} component React component to render the notification UI.
 */

/**
 * Returns the notifications sorted by triggered_at in descending order.
 *
 * Cached via rememo so consumers get a referentially-stable array when `state`
 * hasn't changed, avoiding avoidable re-renders on every store update.
 *
 * @param {Notification[]} state The current state of the marketing notifications store.
 * @return {Notification[]} The sorted notifications.
 */
export const getNotifications = createSelector(
	( state ) => {
		return [ ...state ].sort( ( a, b ) => b.triggered_at - a.triggered_at );
	},
	( state ) => [ state ]
);
