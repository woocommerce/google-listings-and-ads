/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	SHARE_STATUS_FIELD,
	SYNC_OUTCOME_FIELD,
} from '~/components/business-profile-share-control/constants';
import { SHARE_META_KEY, SUMMARY_META_KEY } from '../constants';

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {import('~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus').ShareStatus|null} shareStatus The saved post's share status.
 * @property {import('~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus').SyncOutcome|null} syncOutcome The saved post's last sync outcome.
 * @property {string|null} restPath The post's REST route, or `null` until its post type has loaded.
 * @property {string} savedAt When the post was last saved, as its `modified_gmt`.
 * @property {Function} setShareEnabled Turns sharing on or off, given a boolean.
 * @property {Function} setSummary Sets the text sent to Google.
 */

/**
 * A hook to read and edit the post being edited in the block editor, for sharing it to Google
 * Business Profile. The share choice and the text are the post's edited meta, saved with the
 * post.
 *
 * @return {BusinessProfileSharePost} The post's share state, and setters that edit the post's meta.
 */
export default function useBusinessProfileSharePost() {
	const { editPost } = useDispatch( 'core/editor' );

	const { meta, savedPost, restPath } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		const postType = select( 'core' ).getPostType(
			editor.getCurrentPostType()
		);

		return {
			meta: editor.getEditedPostAttribute( 'meta' ),
			savedPost: editor.getCurrentPost(),
			restPath: postType?.rest_base
				? `/${ postType.rest_namespace ?? 'wp/v2' }/${
						postType.rest_base
					}/${ editor.getCurrentPostId() }`
				: null,
		};
	}, [] );

	const setShareEnabled = ( isShareEnabled ) => {
		editPost( { meta: { [ SHARE_META_KEY ]: isShareEnabled } } );
	};

	const setSummary = ( summary ) => {
		editPost( { meta: { [ SUMMARY_META_KEY ]: summary } } );
	};

	return {
		isShareEnabled: Boolean( meta?.[ SHARE_META_KEY ] ),
		summary: meta?.[ SUMMARY_META_KEY ] ?? '',
		shareStatus: savedPost?.[ SHARE_STATUS_FIELD ] ?? null,
		syncOutcome: savedPost?.[ SYNC_OUTCOME_FIELD ] ?? null,
		restPath,
		savedAt: savedPost?.modified_gmt ?? '',
		setShareEnabled,
		setSummary,
	};
}
