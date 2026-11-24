/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import {
	CHECK_FAILURE_ERROR,
	SYNC_OUTCOME_QUEUED,
	SYNC_OUTCOME_UNSHARE_QUEUED,
} from '../constants';

/**
 * @typedef {import('../types').ShareStatus} ShareStatus
 * @typedef {import('../types').SyncOutcome} SyncOutcome
 */

/**
 * A post's share state after the check, shaped like the check endpoint's response.
 *
 * @typedef {Object} CheckedPost
 * @property {boolean} is_share_enabled Whether sharing is turned on.
 * @property {ShareStatus} share_status The post's share status.
 * @property {SyncOutcome|null} sync_outcome The post's latest sync outcome.
 */

/**
 * @typedef {Object} BusinessProfilePostCheck
 * @property {boolean} isChecking Whether the check is still running.
 * @property {CheckedPost|null} checkedPost The post's share state once Google answered, or `null` while checking, when the check didn't run, or when it failed.
 * @property {'reconnect'|'error'|null} checkFailure Why the check failed, or `null` when it didn't.
 */

/**
 * Whether a post should be checked on Google: it has been shared, and no send or removal is
 * waiting to run.
 *
 * @param {ShareStatus|null} shareStatus The post's share status.
 * @param {SyncOutcome|null} syncOutcome The post's latest sync outcome.
 * @return {boolean} Whether to check the post.
 */
function shouldCheckPost( shareStatus, syncOutcome ) {
	const isWaiting = [
		SYNC_OUTCOME_QUEUED,
		SYNC_OUTCOME_UNSHARE_QUEUED,
	].includes( syncOutcome?.outcome );

	return Boolean( shareStatus?.is_shared ) && ! isWaiting;
}

/**
 * A hook to check once, when the editor opens, whether a post shared to Google Business Profile
 * still exists on Google. The server unlinks the post when it doesn't. Only the state the editor
 * opened with decides whether to check, so sharing the post later in the same session doesn't
 * trigger one.
 *
 * @param {Object} props Hook props.
 * @param {number} props.postId The post's ID.
 * @param {ShareStatus|null} props.shareStatus The post's share status when the editor opened.
 * @param {SyncOutcome|null} props.syncOutcome The post's sync outcome when the editor opened.
 * @return {BusinessProfilePostCheck} The check's progress and result.
 */
export default function useBusinessProfilePostCheck( {
	postId,
	shareStatus,
	syncOutcome,
} ) {
	const [ shouldCheck ] = useState( () => {
		return Boolean( postId ) && shouldCheckPost( shareStatus, syncOutcome );
	} );
	const [ check, setCheck ] = useState( {
		isChecking: shouldCheck,
		checkedPost: null,
		checkFailure: null,
	} );

	useEffect( () => {
		if ( ! shouldCheck ) {
			return;
		}

		let isMounted = true;

		apiFetch( {
			path: `${ API_NAMESPACE }/business-profile/posts/${ postId }/check`,
			method: 'POST',
		} )
			.then( ( response ) => {
				if ( ! isMounted ) {
					return;
				}

				setCheck( {
					isChecking: false,
					checkedPost: response.checked ? response : null,
					checkFailure: response.check_failure ?? null,
				} );
			} )
			.catch( () => {
				if ( ! isMounted ) {
					return;
				}

				// The editor still works without the check, so the panel shows its usual
				// controls with a warning rather than an error notice.
				setCheck( {
					isChecking: false,
					checkedPost: null,
					checkFailure: CHECK_FAILURE_ERROR,
				} );
			} );

		return () => {
			isMounted = false;
		};
	}, [ shouldCheck, postId ] );

	return check;
}
