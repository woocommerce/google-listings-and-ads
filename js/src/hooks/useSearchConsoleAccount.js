/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {Object} SearchConsoleProperty
 * @property {string} url Property URL (domain or URL-prefix identifier).
 * @property {'domain'|'url_prefix'} type Property type.
 * @property {boolean} [selectable] Whether this property covers the store's domain and can be selected. Defaults to `true` when omitted.
 * @property {string} [reason] Explanation shown next to the property when `selectable` is `false`.
 */

/**
 * @typedef {Object} SearchConsoleAccount
 * @property {'connected'|'disconnected'|'incomplete'} status Connection status.
 * @property {'property_selection'|'verification'|'action_needed'|'reconnect'|'connection_failed'|'incomplete'} [step]
 *   Sub-state when `status` is `'incomplete'`.
 * @property {boolean} [skip_auth_prompt] Whether the Google auth prompt should be skipped because the merchant
 *   already has a Merchant Center connection (AC-024). Always backend-supplied, never re-derived on the client.
 * @property {SearchConsoleProperty} [property] The resolved Search Console property, once selected or created.
 * @property {SearchConsoleProperty[]} [properties] Candidate properties to choose from when `step` is `'property_selection'`.
 * @property {boolean} [verified] Whether the resolved property has completed Search Console verification.
 * @property {boolean} [can_self_verify] Whether the merchant can self-verify via the single-click flow (AC-025),
 *   or must be routed to Google's "request access" flow instead (AC-016).
 * @property {string} [request_access_url] External URL to Google's "request access" flow when `can_self_verify` is `false`.
 */

const selectorName = 'getSearchConsoleAccount';

/**
 * A hook to load the connection data of the Google Search Console account.
 *
 * @return {{ searchConsoleAccount: SearchConsoleAccount|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useSearchConsoleAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			searchConsoleAccount: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useSearchConsoleAccount;
