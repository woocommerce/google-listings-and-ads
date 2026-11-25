/**
 * Internal dependencies
 */
import htmlToParagraphs from '~/components/business-profile-share-control/utils/htmlToParagraphs';

/**
 * Get the Classic Editor's current body text, right now, including unsaved edits. Prefers the
 * Visual tab's own HTML, since it already resolves to rendered content only (an image's markup,
 * for example, contributes nothing). Falls back to the raw textarea -- the Text tab, or TinyMCE
 * not having loaded.
 *
 * @return {string} The editor's current body text.
 */
function getLiveClassicEditorText() {
	// ID of the Classic Editor's main content TinyMCE instance and textarea.
	const contentEditorId = 'content';
	const editor = window.tinymce?.get( contentEditorId );

	if ( editor && ! editor.isHidden() ) {
		return htmlToParagraphs( editor.getContent() );
	}

	const textarea = document.getElementById( contentEditorId );

	return textarea ? htmlToParagraphs( textarea.value ) : '';
}

/**
 * Gets the Classic Editor's post title and body text right now, including unsaved edits.
 *
 * @return {{ title: string, body: string }} The post's current title and body, as plain text.
 */
export default function getLiveClassicPostContent() {
	const title = document.getElementById( 'title' )?.value ?? '';
	const body = getLiveClassicEditorText();

	return { title, body };
}
