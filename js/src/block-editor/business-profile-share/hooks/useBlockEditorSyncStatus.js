/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	SHARE_STATUS_FIELD,
	SYNC_OUTCOME_FIELD,
} from '~/components/business-profile-share-control/constants';
import useBusinessProfileSyncStatus from '~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus';

/**
 * @typedef {import('~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus').BusinessProfileSyncStatus} BusinessProfileSyncStatus
 */

/**
 * A hook to keep the Google Business Profile send status of the post being edited in the block
 * editor current. It reads the saved post's share fields, and uses its `modified_gmt` to tell one
 * save from the next.
 *
 * @return {BusinessProfileSyncStatus} The post's current send status.
 */
export default function useBlockEditorSyncStatus() {
	const { postId, syncOutcome, shareStatus, savedAt } = useSelect(
		( select ) => {
			const editor = select( 'core/editor' );
			const savedPost = editor.getCurrentPost();

			return {
				postId: editor.getCurrentPostId(),
				syncOutcome: savedPost?.[ SYNC_OUTCOME_FIELD ] ?? null,
				shareStatus: savedPost?.[ SHARE_STATUS_FIELD ] ?? null,
				savedAt: savedPost?.modified_gmt ?? '',
			};
		},
		[]
	);

	return useBusinessProfileSyncStatus( {
		postId,
		syncOutcome,
		shareStatus,
		savedAt,
	} );
}
