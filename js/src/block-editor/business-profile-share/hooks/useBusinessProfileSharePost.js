/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SHARE_META_KEY, SUMMARY_META_KEY } from '../constants';

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {Function} setShareEnabled Turns sharing on or off, given a boolean.
 * @property {Function} setSummary Sets the text sent to Google.
 */

/**
 * A hook to read and edit the post being edited in the block editor, for sharing it to Google
 * Business Profile. The share choice and the text are the post's edited meta, saved with the
 * post.
 *
 * @return {BusinessProfileSharePost} The post's share choice and text, and setters that edit the post's meta.
 */
export default function useBusinessProfileSharePost() {
	const { editPost } = useDispatch( 'core/editor' );

	const meta = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'meta' );
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
		setShareEnabled,
		setSummary,
	};
}
