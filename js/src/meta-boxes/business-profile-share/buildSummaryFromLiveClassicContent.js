/**
 * Internal dependencies
 */
import { buildSummary } from '~/components/business-profile-share-control/utils/summaryText';

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
	'THEAD',
	'TBODY',
	'TFOOT',
	'TR',
	'TD',
	'TH',
	'FIGURE',
	'FIGCAPTION',
];

// Tags whose own text is read from their children instead of read directly: reading a container's
// own `textContent` would run everything inside it together with no separator (a table's cells, a
// `<div>`'s paragraphs). The same reason Gutenberg's own block walker recurses into
// `core/list-item` rather than reading `core/list`'s own (legacy, unseparated) text.
const CONTAINER_TAGS = [
	'UL',
	'OL',
	'DIV',
	'BLOCKQUOTE',
	'FIGURE',
	'TABLE',
	'THEAD',
	'TBODY',
	'TFOOT',
	'TR',
];

/**
 * Strips WordPress shortcode tags from a text, keeping any text between a pair of tags. Needed on
 * both the Visual tab and the raw textarea: a shortcode-view plugin (gallery, caption, and so on)
 * serializes its view back to literal shortcode syntax whenever `editor.getContent()` is called,
 * so the Visual tab's own HTML can contain a shortcode just as much as the raw textarea can.
 *
 * @param {string} html The HTML to strip shortcode tags from.
 * @return {string} The HTML, with every `[tag ...]`/`[/tag]` removed.
 */
function stripShortcodes( html ) {
	return html.replace( /\[\/?[a-z0-9_-]+[^\]]*\]/gi, '' );
}

/**
 * Collects each top-level paragraph's text, in document order: a block-level element (a heading,
 * a paragraph, a preformatted block, a list, and so on) is its own entry, and a run of inline
 * elements or bare text between block-level elements collects into one entry of its own.
 * Recurses into a container element's children -- a list, a table, a `<div>`, a blockquote, a
 * figure -- instead of reading its own text directly, so each item inside it reads separately.
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

		if ( CONTAINER_TAGS.includes( node.tagName ) ) {
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
 * heading, a paragraph, a preformatted block, a list item, a table cell, and so on), separated by
 * a blank line. This -- rather than a browser or TinyMCE's own HTML-to-text conversion -- is what
 * guarantees a blank line between every block: a native conversion's own spacing isn't consistent
 * for every block-type transition (for example, a heading immediately followed by a preformatted
 * block). Strips shortcode tags first, since the Visual tab's own HTML can contain one (a
 * shortcode-view plugin like gallery or caption serializes back to its shortcode syntax whenever
 * `editor.getContent()` is called), not just the raw textarea.
 *
 * @param {string} html The editor's current HTML content.
 * @return {string} The content as plain text, one blank-line-separated paragraph per block.
 */
function htmlToParagraphs( html ) {
	const fragment = new window.DOMParser().parseFromString(
		stripShortcodes( html ),
		'text/html'
	);

	return collectBlockTexts( fragment.body ).join( '\n\n' );
}

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
 * Builds the Google Business Profile text from the Classic Editor's post right now, including
 * unsaved edits: the live title, followed by the live body text.
 *
 * @return {string} The text to fill the field with.
 */
export default function buildSummaryFromLiveClassicContent() {
	const title = document.getElementById( 'title' )?.value ?? '';
	const body = getLiveClassicEditorText();

	return buildSummary( title, body );
}
