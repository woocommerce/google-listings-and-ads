/**
 * Internal dependencies
 */
import toPlainText from '~/utils/toPlainText';
import { truncateSummary } from './summaryText';

/**
 * Block attributes known to hold a block's own text, by block name. A block not listed here
 * contributes no text of its own, but its inner blocks (if any) are still walked -- this is what
 * lets text nested inside a layout block such as Group or Columns still come through.
 */
const TEXT_ATTRIBUTE_BY_BLOCK_NAME = {
	'core/paragraph': 'content',
	'core/heading': 'content',
	'core/list-item': 'content',
	'core/preformatted': 'content',
	'core/verse': 'content',
	'core/quote': 'value',
	'core/pullquote': 'value',
};

// core/list has no own-text attribute mapped here on purpose: its legacy `values` attribute holds
// every item's HTML concatenated with no separator, so extracting it risks running list items
// together with no space between them. A list built or re-saved in any reasonably current editor
// stores its items as core/list-item inner blocks instead, which are walked normally below.

/**
 * Collect plain text from a block and its inner blocks, recursively. Only a block whose name is
 * in `TEXT_ATTRIBUTE_BY_BLOCK_NAME` contributes its own text (e.g. an Image block never does),
 * but every block's inner blocks are still walked regardless, so text nested inside a layout
 * block (Group, Columns) or a legacy flat Quote/List is not missed.
 *
 * @param {Object} block A block object, as returned by `getBlocks()`.
 * @param {string[]} lines Accumulator for each block's extracted text, in document order -- joined
 *   with a blank line between every entry, so each block reads as its own paragraph.
 */
function collectBlockText( block, lines ) {
	const attribute = TEXT_ATTRIBUTE_BY_BLOCK_NAME[ block.name ];
	const html = attribute ? block.attributes?.[ attribute ] : '';
	const text = html ? toPlainText( html ) : '';

	if ( text ) {
		lines.push( text );
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

	const parts = [ title.trim(), lines.join( '\n\n' ).trim() ].filter(
		Boolean
	);

	return truncateSummary( parts.join( '\n\n' ) );
}
