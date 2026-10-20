/**
 * Internal dependencies
 */
import { toPlainText, truncateSummary } from './buildSummaryFromPost';

// ID of the Classic Editor's main content TinyMCE instance and textarea.
const CONTENT_EDITOR_ID = 'content';

/**
 * Get the Classic Editor's current body text, right now, including unsaved edits. Prefers
 * TinyMCE's own plain-text extraction while the Visual tab is active, since it already resolves
 * to rendered text only (an image's markup, for example, contributes nothing). Falls back to the
 * raw textarea -- the Text tab, or TinyMCE not having loaded -- stripped of any markup the
 * merchant typed directly, the same way the rest of this feature converts HTML to plain text.
 *
 * @return {string} The editor's current body text.
 */
function getLiveClassicEditorText() {
	const editor = window.tinymce?.get( CONTENT_EDITOR_ID );

	if ( editor && ! editor.isHidden() ) {
		return editor.getContent( { format: 'text' } ).trim();
	}

	const textarea = document.getElementById( CONTENT_EDITOR_ID );

	return textarea ? toPlainText( textarea.value ) : '';
}

/**
 * Builds the Google Business Profile text from the Classic Editor's post right now, including
 * unsaved edits: the live title, followed by the live body text.
 *
 * @return {string} The text to fill the field with.
 */
export default function buildSummaryFromLiveClassicContent() {
	const title = document.getElementById( 'title' )?.value ?? '';
	const body = getLiveClassicEditorText();

	const parts = [ title.trim(), body ].filter( Boolean );

	return truncateSummary( parts.join( '\n\n' ) );
}
