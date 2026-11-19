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

// Nodes to skip entirely, contributing no text at all: a <script>/<style> tag's own textContent is
// code, not post content, and an HTML comment's textContent is just the text between `<!--` and
// `-->` (e.g. a bare `more` for `<!--more-->`), not a real comment node's visible text.
const SKIP_TAGS = [ 'SCRIPT', 'STYLE' ];

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
 * Skips a `<script>`/`<style>` tag and an HTML comment node entirely -- neither is visible content.
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
		if ( node.nodeType === node.COMMENT_NODE ) {
			return;
		}

		if (
			node.nodeType === node.ELEMENT_NODE &&
			SKIP_TAGS.includes( node.tagName )
		) {
			return;
		}

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
 * Converts HTML to plain-text paragraphs, one entry per top-level block element (a heading, a
 * paragraph, a preformatted block, a list item, a table cell, and so on). This -- rather than a
 * browser or TinyMCE's own HTML-to-text conversion -- is what guarantees a blank line between
 * every block: a native conversion's own spacing isn't consistent for every block-type transition
 * (for example, a heading immediately followed by a preformatted block). Strips shortcode tags
 * first, since a shortcode-view plugin (gallery, caption, and so on) can serialize its view back
 * to literal shortcode syntax.
 *
 * @param {string} html The HTML to convert.
 * @return {string[]} Each paragraph's trimmed text, in document order.
 */
export function htmlToParagraphList( html ) {
	const fragment = new window.DOMParser().parseFromString(
		stripShortcodes( html ),
		'text/html'
	);

	return collectBlockTexts( fragment.body );
}

/**
 * Converts HTML to plain text, one paragraph per top-level block element, separated by a blank
 * line.
 *
 * @param {string} html The HTML to convert.
 * @return {string} The content as plain text, one blank-line-separated paragraph per block.
 */
export default function htmlToParagraphs( html ) {
	return htmlToParagraphList( html ).join( '\n\n' );
}
