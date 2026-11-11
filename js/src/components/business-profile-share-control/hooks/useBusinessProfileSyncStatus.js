/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import useBoundedPolling from './useBoundedPolling';
import {
	SHARE_STATUS_FIELD,
	SYNC_OUTCOME_FIELD,
	SYNC_OUTCOME_QUEUED,
} from '../constants';

/**
 * Seconds between checks while a send is queued.
 */
const POLL_INTERVAL_SECONDS = 5;

/**
 * The most checks per queued send. With the interval above, checking stops after 90 seconds, which
 * covers the usual wait before the background send runs.
 */
const MAX_POLL_ATTEMPTS = 18;

/**
 * @typedef {Object} SyncOutcome
 * @property {string} outcome The outcome of the post's last send to Google, e.g. `queued` or `success`.
 * @property {string|null} display_message The message to show the merchant, or `null` when there's nothing to tell them.
 * @property {string|null} display_status The message's severity: `error` or `warning`, or `null` without a message.
 */

/**
 * @typedef {Object} ShareStatus
 * @property {boolean} is_shared Whether the post has been shared to Google.
 * @property {string} search_url The post's link on Google, once Google provides one.
 */

/**
 * @typedef {Object} BusinessProfileSyncStatus
 * @property {SyncOutcome|null} syncOutcome The latest sync outcome, or `null` when the post has never been sent.
 * @property {boolean} isShared Whether the post has been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 * @property {boolean} isChecking Whether a queued send is still being checked on.
 */

/**
 * Returns whether the post response no longer reports a queued send.
 *
 * @param {Object} post The post's REST response, limited to the share fields.
 * @return {boolean} Whether checking can stop.
 */
const isSettled = ( post ) => {
	return post?.[ SYNC_OUTCOME_FIELD ]?.outcome !== SYNC_OUTCOME_QUEUED;
};

/**
 * A hook to keep a post's Google Business Profile send status current while its send is queued.
 * It checks the post's REST fields on an interval until the send settles or the checks run out,
 * and starts over after each save.
 *
 * @param {Object} props Hook props.
 * @param {string|null} props.restPath The post's REST route, e.g. `/wp/v2/posts/123`, or `null` when it's not known yet.
 * @param {SyncOutcome|null} props.syncOutcome The sync outcome the editor has for the post.
 * @param {ShareStatus|null} props.shareStatus The share status the editor has for the post.
 * @param {string} [props.savedAt] When the post was last saved, e.g. its `modified_gmt`. A new value starts a new round of checks, even when the outcome is `queued` both before and after the save.
 * @return {BusinessProfileSyncStatus} The post's current send status.
 */
export default function useBusinessProfileSyncStatus( {
	restPath,
	syncOutcome,
	shareStatus,
	savedAt = '',
} ) {
	// The save the current round of checks belongs to, or `null` before the first round.
	const [ checkedSave, setCheckedSave ] = useState( null );
	const isQueued = syncOutcome?.outcome === SYNC_OUTCOME_QUEUED;

	const { start, data, hasReachedMaxAttempts } = useBoundedPolling( {
		path:
			restPath &&
			addQueryArgs( restPath, {
				context: 'edit',
				_fields: [ SYNC_OUTCOME_FIELD, SHARE_STATUS_FIELD ].join( ',' ),
			} ),
		intervalSeconds: POLL_INTERVAL_SECONDS,
		maxAttempts: MAX_POLL_ATTEMPTS,
		isDone: isSettled,
	} );

	useEffect( () => {
		if ( ! isQueued || ! restPath ) {
			return;
		}

		setCheckedSave( savedAt );
		start();
	}, [ isQueued, restPath, savedAt, start ] );

	// Only use checked data for the save that started the check, so a later save's own outcome
	// isn't hidden behind data from an earlier round.
	const isCurrentRound = checkedSave === savedAt;
	const latest = isCurrentRound && data ? data : null;
	const currentOutcome = latest ? latest[ SYNC_OUTCOME_FIELD ] : syncOutcome;
	const currentShareStatus = latest
		? latest[ SHARE_STATUS_FIELD ]
		: shareStatus;
	const isStillQueued = currentOutcome?.outcome === SYNC_OUTCOME_QUEUED;
	const hasStoppedChecking = isCurrentRound && hasReachedMaxAttempts;

	return {
		syncOutcome: currentOutcome ?? null,
		isShared: Boolean( currentShareStatus?.is_shared ),
		searchUrl: currentShareStatus?.search_url ?? '',
		isChecking:
			isStillQueued && Boolean( restPath ) && ! hasStoppedChecking,
	};
}
