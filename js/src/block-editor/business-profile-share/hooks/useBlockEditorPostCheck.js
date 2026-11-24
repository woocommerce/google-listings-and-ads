/**
 * External dependencies
 */
import { useRegistry, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	SHARE_STATUS_FIELD,
	SYNC_OUTCOME_FIELD,
} from '~/components/business-profile-share-control/constants';
import useBusinessProfilePostCheck from '~/components/business-profile-share-control/hooks/useBusinessProfilePostCheck';
import { SHARE_META_KEY } from '../constants';

/**
 * @typedef {Object} BlockEditorPostCheck
 * @property {boolean} isChecking Whether the check is still running.
 * @property {'reconnect'|'error'|null} checkFailure Why the check failed, or `null` when it didn't.
 */

/**
 * A hook to check, when the block editor opens, whether the post being edited still exists on
 * Google Business Profile. Once Google answers, the post's saved record takes the server's share
 * state, so a post the server unlinked shows its sharing off and no link. The saved record
 * changes rather than the post's edits, so the post isn't marked as having unsaved changes.
 *
 * @return {BlockEditorPostCheck} The check's progress.
 */
export default function useBlockEditorPostCheck() {
	const registry = useRegistry();
	const { postId, postType, shareStatus, syncOutcome } = useSelect(
		( select ) => {
			const editor = select( 'core/editor' );
			const savedPost = editor.getCurrentPost();

			return {
				postId: editor.getCurrentPostId(),
				postType: editor.getCurrentPostType(),
				shareStatus: savedPost?.[ SHARE_STATUS_FIELD ] ?? null,
				syncOutcome: savedPost?.[ SYNC_OUTCOME_FIELD ] ?? null,
			};
		},
		[]
	);

	const { isChecking, checkedPost, checkFailure } =
		useBusinessProfilePostCheck( { postId, shareStatus, syncOutcome } );

	useEffect( () => {
		if ( ! checkedPost ) {
			return;
		}

		const record = registry
			.select( 'core' )
			.getEntityRecord( 'postType', postType, postId );

		if ( ! record ) {
			return;
		}

		registry
			.dispatch( 'core' )
			.receiveEntityRecords( 'postType', postType, [
				{
					...record,
					meta: {
						...record.meta,
						[ SHARE_META_KEY ]: checkedPost.is_share_enabled,
					},
					[ SHARE_STATUS_FIELD ]: checkedPost.share_status,
					[ SYNC_OUTCOME_FIELD ]: checkedPost.sync_outcome,
				},
			] );
	}, [ checkedPost, registry, postType, postId ] );

	return { isChecking, checkFailure };
}
