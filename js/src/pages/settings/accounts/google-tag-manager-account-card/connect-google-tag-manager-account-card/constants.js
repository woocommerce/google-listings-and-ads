/**
 * The not-yet-connected card's own sub-steps. Local to this subtree (distinct from the top-level
 * `GOOGLE_TAG_MANAGER_ACCOUNT_STATUS`, which switches between this card, the container-selection
 * card, and the connected card) — `CONNECTION_FAILED` is observed directly from a failed connect
 * attempt, not reported by the backend, since this card's connect request never navigates away
 * from the page.
 *
 * @enum {string}
 */
export const CONNECT_STEP = {
	ACCOUNT_SELECTION: 'account_selection',
	CONNECTION_FAILED: 'connection_failed',
};
