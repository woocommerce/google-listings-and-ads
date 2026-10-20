/**
 * Internal dependencies
 */
import { truncateSummary } from './summaryText';

// ID of the Classic Editor's main content TinyMCE instance and textarea.
const CONTENT_EDITOR_ID = 'content';

// Tag names that start a new paragraph of their own. Anything else -- an inline element such as
// `<strong>`, or bare text -- accumulates into the paragraph it's part of, rather than becoming
// an entry of its own: a run of inline formatting with no wrapping block tag should still read as
// one paragraph.
const BLOCK_LEVEL_TAGS = [
	'P',
	'H1',
	'H2',
	'H3',
	'H4',
	'H5',
	'H6',
	'PRE',
	'BLOCKQUOTE',
	'UL',
	'OL',
	'LI',
	'DIV',
	'TABLE',
	'FIGURE',
];

// A list's own text is read from its items instead, so items don't run together with no
// separator -- the same reason Gutenberg's own block walker recurses into `core/list-item`.
const LIST_CONTAINER_TAGS = [ 'UL', 'OL' ];

/**
 * Strips WordPress shortcode tags from a text, keeping any text between a pair of tags. Only the
 * raw textarea -- the Text tab, or TinyMCE not having loaded -- can contain an un-rendered
 * shortcode like `[caption]`; the Visual tab's TinyMCE view already renders a shortcode's own
 * markup away before `htmlToParagraphs()` ever reads its text.
 *
 * @param {string} text The text to strip shortcode tags from.
 * @return {string} The text, with every `[tag ...]`/`[/tag]` removed.
 */
function stripShortcodes( text ) {
	return text.replace( /\[\/?[a-z0-9_-]+[^\]]*\]/gi, '' );
}

/**
 * Collects each top-level paragraph's text, in document order: a block-level element (a heading,
 * a paragraph, a preformatted block, a list, and so on) is its own entry, and a run of inline
 * elements or bare text between block-level elements collects into one entry of its own.
 * Recurses into a list's items instead of reading the list's own text directly, so each item
 * reads separately.
 *
 * @param {Node} container The node whose children to walk.
 * @return {string[]} Each paragraph's trimmed text, in document order.
 */
function collectBlockTexts( container ) {
	const texts = [];
	let inlineBuffer = '';

	const flushInlineBuffer = () => {
		const text = inlineBuffer.trim();

		if ( text ) {
			texts.push( text );
		}

		inlineBuffer = '';
	};

	Array.from( container.childNodes ).forEach( ( node ) => {
		if (
			node.nodeType !== node.ELEMENT_NODE ||
			! BLOCK_LEVEL_TAGS.includes( node.tagName )
		) {
			inlineBuffer += node.textContent || '';
			return;
		}

		flushInlineBuffer();

		if ( LIST_CONTAINER_TAGS.includes( node.tagName ) ) {
			texts.push( ...collectBlockTexts( node ) );
			return;
		}

		const text = ( node.textContent || '' ).trim();

		if ( text ) {
			texts.push( text );
		}
	} );

	flushInlineBuffer();

	return texts;
}

/**
 * Converts an editor's HTML content to plain text, one paragraph per top-level block element (a
 * heading, a paragraph, a preformatted block, a list item, and so on), separated by a blank line.
 * This -- rather than a browser or TinyMCE's own HTML-to-text conversion -- is what guarantees a
 * blank line between every block: a native conversion's own spacing isn't consistent for every
 * block-type transition (for example, a heading immediately followed by a preformatted block).
 *
 * @param {string} html The editor's current HTML content.
 * @return {string} The content as plain text, one blank-line-separated paragraph per block.
 */
function htmlToParagraphs( html ) {
	const fragment = new window.DOMParser().parseFromString(
		html,
		'text/html'
	);

	return collectBlockTexts( fragment.body ).join( '\n\n' );
}

/**
 * Get the Classic Editor's current body text, right now, including unsaved edits. Prefers the
 * Visual tab's own HTML, since it already resolves to rendered content only (an image's markup,
 * for example, contributes nothing). Falls back to the raw textarea -- the Text tab, or TinyMCE
 * not having loaded -- stripped of any shortcode tags the merchant typed directly.
 *
 * @return {string} The editor's current body text.
 */
function getLiveClassicEditorText() {
	const editor = window.tinymce?.get( CONTENT_EDITOR_ID );

	if ( editor && ! editor.isHidden() ) {
		return htmlToParagraphs( editor.getContent() );
	}

	const textarea = document.getElementById( CONTENT_EDITOR_ID );

	return textarea
		? htmlToParagraphs( stripShortcodes( textarea.value ) )
		: '';
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
