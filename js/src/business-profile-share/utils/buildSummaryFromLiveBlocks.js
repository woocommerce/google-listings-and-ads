/**
 * External dependencies
 */
import { __unstableStripHTML as stripHTML } from '@wordpress/dom'; // eslint-disable-line @wordpress/no-unsafe-wp-apis

/**
 * Internal dependencies
 */
import { buildSummary } from './summaryText';

/**
 * Converts a block attribute's HTML to trimmed plain text. A rich-text attribute holds a
 * `RichTextData` instance rather than a string, so it's converted to its HTML string first.
 *
 * @param {string|{toString: Function}} html The attribute's HTML.
 * @return {string} The attribute's text content, trimmed.
 */
function toPlainText( html ) {
	return stripHTML( String( html ) ).trim();
}

/**
 * Block attributes known to hold a block's own text, by block name -- each contributing its own
 * separate entry. A block not listed here contributes no text of its own, but its inner blocks
 * (if any) are still walked -- this is what lets text nested inside a layout block such as Group
 * or Columns still come through.
 */
const TEXT_ATTRIBUTES_BY_BLOCK_NAME = {
	'core/paragraph': [ 'content' ],
	'core/heading': [ 'content' ],
	'core/list-item': [ 'content' ],
	'core/preformatted': [ 'content' ],
	'core/verse': [ 'content' ],
	// A quote's own attribution (e.g. "— Jane Doe, Happy Customer") is separate from its quoted
	// text, and is easy to miss since it's not part of the quote's own value/innerBlocks content.
	'core/quote': [ 'value', 'citation' ],
	'core/pullquote': [ 'value', 'citation' ],
	// A details block's own clickable heading (e.g. "Returns Policy") isn't part of its expandable
	// body, which is read separately via its inner blocks.
	'core/details': [ 'summary' ],
};

// core/list has no own-text attribute mapped here on purpose: its legacy `values` attribute holds
// every item's HTML concatenated with no separator, so extracting it risks running list items
// together with no space between them. A list built or re-saved in any reasonably current editor
// stores its items as core/list-item inner blocks instead, which are walked normally below.

/**
 * Collects a `core/table` block's own text: each cell's content as its own entry, plus the
 * table's caption if it has one. A table stores its content as structured attribute data (head,
 * body, and foot arrays of rows and cells) rather than as inner blocks, so -- unlike every other
 * block here -- it needs its own extraction instead of a single attribute lookup. Each cell is
 * pushed separately, the same way a Classic Editor table's cells are read one at a time, so a row
 * like "Mon" / "9–5" doesn't run together into "Mon9–5".
 *
 * @param {Object} block A `core/table` block.
 * @param {string[]} lines Accumulator to push each cell's and the caption's text into.
 */
function collectTableText( block, lines ) {
	[ 'head', 'body', 'foot' ].forEach( ( section ) => {
		( block.attributes?.[ section ] || [] ).forEach( ( row ) => {
			( row.cells || [] ).forEach( ( cell ) => {
				const text = cell.content ? toPlainText( cell.content ) : '';

				if ( text ) {
					lines.push( text );
				}
			} );
		} );
	} );

	const caption = block.attributes?.caption
		? toPlainText( block.attributes.caption )
		: '';

	if ( caption ) {
		lines.push( caption );
	}
}

/**
 * Collect plain text from a block and its inner blocks, recursively. Only a block whose name is
 * in `TEXT_ATTRIBUTES_BY_BLOCK_NAME` contributes its own text (e.g. an Image block never does),
 * but every block's inner blocks are still walked regardless, so text nested inside a layout
 * block (Group, Columns) or a legacy flat Quote/List is not missed. `core/table` is a special
 * case, since its content lives in attribute data rather than inner blocks or a flat attribute.
 *
 * @param {Object} block A block object, as returned by `getBlocks()`.
 * @param {string[]} lines Accumulator for each block's extracted text, in document order -- joined
 *   with a blank line between every entry, so each block reads as its own paragraph.
 */
function collectBlockText( block, lines ) {
	if ( block.name === 'core/table' ) {
		collectTableText( block, lines );
	} else {
		const attributes = TEXT_ATTRIBUTES_BY_BLOCK_NAME[ block.name ] || [];

		attributes.forEach( ( attribute ) => {
			const html = block.attributes?.[ attribute ];
			const text = html ? toPlainText( html ) : '';

			if ( text ) {
				lines.push( text );
			}
		} );
	}

	( block.innerBlocks || [] ).forEach( ( innerBlock ) => {
		collectBlockText( innerBlock, lines );
	} );
}

/**
 * Builds the Google Business Profile text from the post as it is in the editor right now,
 * including unsaved edits: the live title, followed by the text of every text-bearing block in
 * document order, each separated by a blank line as its own paragraph. Non-text blocks (images,
 * embeds, buttons, and so on) contribute nothing.
 *
 * @param {Object} post
 * @param {string} post.title The post's current (possibly unsaved) title.
 * @param {Object[]} post.blocks The post's current (possibly unsaved) blocks, as returned by `getBlocks()`.
 * @return {string} The text to fill the field with.
 */
export default function buildSummaryFromLiveBlocks( { title, blocks } ) {
	const lines = [];

	blocks.forEach( ( block ) => collectBlockText( block, lines ) );

	return buildSummary( title, lines.join( '\n\n' ) );
}
