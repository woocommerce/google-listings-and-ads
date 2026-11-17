/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { addAction, removeAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { HEARTBEAT_SYNC_STATUS_KEY, SYNC_OUTCOME_QUEUED } from '../constants';

/**
 * @typedef {import('../types').SyncOutcome} SyncOutcome
 * @typedef {import('../types').ShareStatus} ShareStatus
 */

/**
 * @typedef {Object} BusinessProfileSyncStatus
 * @property {SyncOutcome|null} syncOutcome The latest sync outcome, or `null` when the post has never been sent.
 * @property {boolean} isShared Whether the post has been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 */

/**
 * Namespace of the Heartbeat actions this hook adds.
 */
const HOOK_NAMESPACE = 'google-listings-and-ads/business-profile-sync-status';

/**
 * How long after a save that queues a send to ask for the post's status, in milliseconds, so a
 * quick send shows without waiting for the editor's next Heartbeat request.
 */
export const FIRST_CHECK_DELAY = 3000;

/**
 * A hook to keep a post's Google Business Profile send status current while its send is queued.
 * While the send is queued, it asks for the post's status on each WordPress Heartbeat request, at
 * whatever interval the editor sets, since a background send can take minutes to run. It also
 * sends one request {@link FIRST_CHECK_DELAY} after each save, so a quick send shows sooner. Once
 * the send settles, it stops asking. Where Heartbeat isn't loaded, it returns the editor's values.
 *
 * @param {Object} props Hook props.
 * @param {number} props.postId The post's ID.
 * @param {SyncOutcome|null} props.syncOutcome The sync outcome the editor has for the post.
 * @param {ShareStatus|null} props.shareStatus The share status the editor has for the post.
 * @param {string} [props.savedAt] When the post was last saved, e.g. its `modified_gmt`. A new value starts over, even when the outcome is `queued` both before and after the save.
 * @return {BusinessProfileSyncStatus} The post's current send status.
 */
export default function useBusinessProfileSyncStatus( {
	postId,
	syncOutcome,
	shareStatus,
	savedAt = '',
} ) {
	// The latest status Heartbeat returned, and the save the request belongs to.
	const [ latest, setLatest ] = useState( null );

	// Only use a status asked for since the last save, so a later save's own outcome isn't hidden
	// behind a status returned for an earlier one.
	const isCurrent = latest?.savedAt === savedAt;
	const currentOutcome = isCurrent ? latest.syncOutcome : syncOutcome;
	const currentShareStatus = isCurrent ? latest.shareStatus : shareStatus;
	const isQueued = currentOutcome?.outcome === SYNC_OUTCOME_QUEUED;

	useEffect( () => {
		const heartbeat = window.wp?.heartbeat;

		if ( ! isQueued || ! postId || ! heartbeat ) {
			return;
		}

		// A save replaces these actions while a request may still be on its way. Its reply then
		// describes the post before this save, so only replies to requests sent from here count.
		let hasAsked = false;

		addAction( 'heartbeat.send', HOOK_NAMESPACE, ( data ) => {
			hasAsked = true;
			data[ HEARTBEAT_SYNC_STATUS_KEY ] = postId;
		} );
		addAction( 'heartbeat.tick', HOOK_NAMESPACE, ( response ) => {
			const status = response?.[ HEARTBEAT_SYNC_STATUS_KEY ];

			if ( status && hasAsked ) {
				setLatest( {
					savedAt,
					syncOutcome: status.syncOutcome ?? null,
					shareStatus: status.shareStatus ?? null,
				} );
			}
		} );

		const firstCheck = setTimeout( () => {
			heartbeat.connectNow();
		}, FIRST_CHECK_DELAY );

		return () => {
			clearTimeout( firstCheck );
			removeAction( 'heartbeat.send', HOOK_NAMESPACE );
			removeAction( 'heartbeat.tick', HOOK_NAMESPACE );
		};
	}, [ isQueued, postId, savedAt ] );

	return {
		syncOutcome: currentOutcome ?? null,
		isShared: Boolean( currentShareStatus?.is_shared ),
		searchUrl: currentShareStatus?.search_url ?? '',
	};
}
