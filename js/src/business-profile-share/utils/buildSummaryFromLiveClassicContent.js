/**
 * Internal dependencies
 */
import toPlainText from '~/utils/toPlainText';
import { truncateSummary } from './summaryText';

// ID of the Classic Editor's main content TinyMCE instance and textarea.
const CONTENT_EDITOR_ID = 'content';

/**
 * Strips WordPress shortcode tags from a text, keeping any text between a pair of tags. Only the
 * raw textarea -- the Text tab, or TinyMCE not having loaded -- can contain an un-rendered
 * shortcode like `[caption]`; the Visual tab's TinyMCE view already renders a shortcode's own
 * markup away before `getLiveClassicEditorText()` ever reads its text.
 *
 * @param {string} text The text to strip shortcode tags from.
 * @return {string} The text, with every `[tag ...]`/`[/tag]` removed.
 */
function stripShortcodes( text ) {
	return text.replace( /\[\/?[a-z0-9_-]+[^\]]*\]/gi, '' );
}

/**
 * Get the Classic Editor's current body text, right now, including unsaved edits. Prefers
 * TinyMCE's own plain-text extraction while the Visual tab is active, since it already resolves
 * to rendered text only (an image's markup, for example, contributes nothing). Falls back to the
 * raw textarea -- the Text tab, or TinyMCE not having loaded -- stripped of any shortcode tags and
 * markup the merchant typed directly, the same way the rest of this feature converts HTML to
 * plain text.
 *
 * @return {string} The editor's current body text.
 */
function getLiveClassicEditorText() {
	const editor = window.tinymce?.get( CONTENT_EDITOR_ID );

	if ( editor && ! editor.isHidden() ) {
		return editor.getContent( { format: 'text' } ).trim();
	}

	const textarea = document.getElementById( CONTENT_EDITOR_ID );

	return textarea ? toPlainText( stripShortcodes( textarea.value ) ) : '';
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
