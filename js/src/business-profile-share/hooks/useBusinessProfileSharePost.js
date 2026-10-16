/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	NEVER_SAVED_POST_STATUS,
	SHARE_META_KEY,
	SHARE_STATUS_FIELD,
	SUMMARY_META_KEY,
} from '../constants';
import buildSummaryFromPost from '../utils/buildSummaryFromPost';

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {boolean} isNeverSaved Whether the post has never been saved.
 * @property {boolean} isShared Whether the post has already been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 * @property {Function} getGeneratedSummary Returns the text built from the saved post.
 * @property {Function} setShareEnabled Turns sharing on or off, given a boolean.
 * @property {Function} setSummary Sets the text sent to Google.
 */

/**
 * A hook to read and edit the post being edited in the block editor, for sharing it to Google
 * Business Profile. The share choice and the text are the post's edited meta, saved with the
 * post. Everything else reflects the post as last saved, so "Generate from post" never uses
 * unsaved edits.
 *
 * @return {BusinessProfileSharePost} The post's share state, and setters that edit the post's meta.
 */
export default function useBusinessProfileSharePost() {
	const { editPost } = useDispatch( 'core/editor' );

	const { meta, savedPost, renderedExcerpt } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		const record = select( 'core' ).getEntityRecord(
			'postType',
			editor.getCurrentPostType(),
			editor.getCurrentPostId()
		);

		return {
			meta: editor.getEditedPostAttribute( 'meta' ),
			savedPost: editor.getCurrentPost(),
			renderedExcerpt: record?.excerpt?.rendered ?? '',
		};
	}, [] );

	const shareStatus = savedPost?.[ SHARE_STATUS_FIELD ];

	const getGeneratedSummary = () => {
		return buildSummaryFromPost( {
			title: savedPost?.title ?? '',
			hasManualExcerpt: Boolean( savedPost?.excerpt?.trim() ),
			renderedExcerpt,
		} );
	};

	const setShareEnabled = ( isShareEnabled ) => {
		editPost( { meta: { [ SHARE_META_KEY ]: isShareEnabled } } );
	};

	const setSummary = ( summary ) => {
		editPost( { meta: { [ SUMMARY_META_KEY ]: summary } } );
	};

	return {
		isShareEnabled: Boolean( meta?.[ SHARE_META_KEY ] ),
		summary: meta?.[ SUMMARY_META_KEY ] ?? '',
		isNeverSaved: savedPost?.status === NEVER_SAVED_POST_STATUS,
		isShared: Boolean( shareStatus?.is_shared ),
		searchUrl: shareStatus?.search_url ?? '',
		getGeneratedSummary,
		setShareEnabled,
		setSummary,
	};
}
