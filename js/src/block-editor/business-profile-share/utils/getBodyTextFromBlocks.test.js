/**
 * External dependencies
 */
import { RichTextData } from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import getBodyTextFromBlocks from './getBodyTextFromBlocks';

/**
 * A minimal block object, shaped the way `getBlocks()` returns one.
 *
 * @param {string} name Block name, e.g. `'core/paragraph'`.
 * @param {Object} [attributes] The block's attributes.
 * @param {Object[]} [innerBlocks] The block's inner blocks.
 * @return {Object} The block.
 */
function block( name, attributes = {}, innerBlocks = [] ) {
	return { name, attributes, innerBlocks };
}

describe( 'getBodyTextFromBlocks', () => {
	it( "returns a single block's text", () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/paragraph', {
				content: 'Our summer range is now in store.',
			} ),
		] );

		expect( body ).toBe( 'Our summer range is now in store.' );
	} );

	it( 'separates every block with a blank line, including around a heading', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/paragraph', {
				content: 'This is a paragraph',
			} ),
			block( 'core/heading', { content: 'This is a heading' } ),
			block( 'core/paragraph', {
				content: 'This is another paragraph',
			} ),
		] );

		expect( body ).toBe(
			'This is a paragraph\n\nThis is a heading\n\nThis is another paragraph'
		);
	} );

	it( 'skips a non-text block entirely', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/image', { url: 'https://example.com/a.jpg' } ),
			block( 'core/paragraph', { content: 'Doors open at 6pm.' } ),
		] );

		expect( body ).toBe( 'Doors open at 6pm.' );
	} );

	it( 'reads text nested inside a layout block', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/group', {}, [
				block( 'core/paragraph', {
					content: 'Nested inside a Group block.',
				} ),
			] ),
		] );

		expect( body ).toBe( 'Nested inside a Group block.' );
	} );

	it( 'reads a list built from list-item inner blocks, keeping items apart', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/list', {}, [
				block( 'core/list-item', { content: 'First item' } ),
				block( 'core/list-item', { content: 'Second item' } ),
			] ),
		] );

		expect( body ).toBe( 'First item\n\nSecond item' );
	} );

	it( 'reads a legacy flat Quote block from its value attribute', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/quote', {
				value: '<p>A happy customer.</p>',
			} ),
		] );

		expect( body ).toBe( 'A happy customer.' );
	} );

	it( 'reads a quote’s citation separately from its quoted text', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/quote', {
				value: '<p>Best decision for our business.</p>',
				citation: 'Jane Doe, Happy Customer',
			} ),
		] );

		expect( body ).toBe(
			'Best decision for our business.\n\nJane Doe, Happy Customer'
		);
	} );

	it( "reads a details block's own summary heading, separately from its expandable body", () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/details', { summary: 'Returns Policy' }, [
				block( 'core/paragraph', {
					content: 'Returns accepted within 30 days.',
				} ),
			] ),
		] );

		expect( body ).toBe(
			'Returns Policy\n\nReturns accepted within 30 days.'
		);
	} );

	it( 'reads a table one cell at a time, instead of running every cell together', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/table', {
				head: [
					{
						cells: [
							{ content: 'Day', tag: 'th' },
							{ content: 'Hours', tag: 'th' },
						],
					},
				],
				body: [
					{
						cells: [
							{ content: 'Mon', tag: 'td' },
							{ content: '9–5', tag: 'td' },
						],
					},
				],
			} ),
		] );

		expect( body ).toBe( 'Day\n\nHours\n\nMon\n\n9–5' );
	} );

	it( "reads a table's caption after its cells", () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/table', {
				body: [ { cells: [ { content: 'Mon', tag: 'td' } ] } ],
				caption: 'Store hours',
			} ),
		] );

		expect( body ).toBe( 'Mon\n\nStore hours' );
	} );

	it( 'strips HTML from a block’s content', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/paragraph', {
				content: '<strong>Bold</strong> and <em>italic</em> text.',
			} ),
		] );

		expect( body ).toBe( 'Bold and italic text.' );
	} );

	it( 'returns an empty body when there are no text-bearing blocks', () => {
		const body = getBodyTextFromBlocks( [ block( 'core/image', {} ) ] );

		expect( body ).toBe( '' );
	} );

	it( "returns empty text when there's nothing", () => {
		const body = getBodyTextFromBlocks( [] );

		expect( body ).toBe( '' );
	} );

	it( 'reads a rich-text attribute held as RichTextData, decoding its entities', () => {
		const body = getBodyTextFromBlocks( [
			block( 'core/paragraph', {
				content: RichTextData.fromHTMLString(
					'<strong>Shoes</strong> &amp; boots'
				),
			} ),
		] );

		expect( body ).toBe( 'Shoes & boots' );
	} );
} );
