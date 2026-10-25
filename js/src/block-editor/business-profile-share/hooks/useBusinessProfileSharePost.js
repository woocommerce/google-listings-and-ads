/**
 * External dependencies
 */
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	SHARE_META_KEY,
	SHARE_STATUS_FIELD,
	SUMMARY_META_KEY,
} from '../constants';
import getLivePostContentFromBlocks from '../utils/getLivePostContentFromBlocks';

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {boolean} isShared Whether the post has already been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 * @property {Function} getLivePostContent Returns the post's title and body right now, including unsaved edits, as plain text.
 * @property {Function} setShareEnabled Turns sharing on or off, given a boolean.
 * @property {Function} setSummary Sets the text sent to Google.
 */

/**
 * A hook to read and edit the post being edited in the block editor, for sharing it to Google
 * Business Profile. The share choice and the text are the post's edited meta, saved with the
 * post. "Generate from post" reads the post as it is right now, including unsaved edits -- see
 * {@see getLivePostContentFromBlocks}.
 *
 * @return {BusinessProfileSharePost} The post's share state, and setters that edit the post's meta.
 */
export default function useBusinessProfileSharePost() {
	const { editPost } = useDispatch( 'core/editor' );
	const registry = useRegistry();

	const { meta, savedPost } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		return {
			meta: editor.getEditedPostAttribute( 'meta' ),
			savedPost: editor.getCurrentPost(),
		};
	}, [] );

	const shareStatus = savedPost?.[ SHARE_STATUS_FIELD ];

	// Reads the title and blocks through the registry, rather than `useSelect`, so the panel
	// doesn't re-render on every edit anywhere in the post -- their current value is only ever
	// needed at the moment the merchant clicks "Generate from post".
	const getLivePostContent = () => {
		const title = registry
			.select( 'core/editor' )
			.getEditedPostAttribute( 'title' );
		const blocks = registry.select( 'core/block-editor' ).getBlocks();

		return getLivePostContentFromBlocks( { title: title ?? '', blocks } );
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
		isShared: Boolean( shareStatus?.is_shared ),
		searchUrl: shareStatus?.search_url ?? '',
		getLivePostContent,
		setShareEnabled,
		setSummary,
	};
}
