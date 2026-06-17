/**
 * @typedef {Object} Notification
 * @property {string} id Unique identifier.
 * @property {number} triggered_at Unix timestamp when notification was triggered.
 * @property {Function} component React component to render the notification UI.
 */

/**
 * Returns the notifications sorted by triggered_at in descending order.
 *
 * @param {Notification[]} state The current state of the marketing notifications store.
 * @return {Notification[]} The sorted notifications.
 */
export const getNotifications = ( state ) => {
	return [ ...state ].sort( ( a, b ) => b.triggered_at - a.triggered_at );
};
