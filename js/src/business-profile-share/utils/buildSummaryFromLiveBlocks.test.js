/**
 * Internal dependencies
 */
import buildSummaryFromLiveBlocks from './buildSummaryFromLiveBlocks';

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

describe( 'buildSummaryFromLiveBlocks', () => {
	it( 'joins the title and a single block with a blank line', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: 'New summer collection just landed',
			blocks: [
				block( 'core/paragraph', {
					content: 'Our summer range is now in store.',
				} ),
			],
		} );

		expect( summary ).toBe(
			'New summer collection just landed\n\nOur summer range is now in store.'
		);
	} );

	it( 'separates every block with a blank line, including around a heading', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: 'Testing Block Editor',
			blocks: [
				block( 'core/paragraph', {
					content: 'This is a paragraph',
				} ),
				block( 'core/heading', { content: 'This is a heading' } ),
				block( 'core/paragraph', {
					content: 'This is another paragraph',
				} ),
			],
		} );

		expect( summary ).toBe(
			'Testing Block Editor\n\nThis is a paragraph\n\nThis is a heading\n\nThis is another paragraph'
		);
	} );

	it( 'skips a non-text block entirely', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: 'Gallery opening',
			blocks: [
				block( 'core/image', { url: 'https://example.com/a.jpg' } ),
				block( 'core/paragraph', { content: 'Doors open at 6pm.' } ),
			],
		} );

		expect( summary ).toBe( 'Gallery opening\n\nDoors open at 6pm.' );
	} );

	it( 'reads text nested inside a layout block', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: '',
			blocks: [
				block( 'core/group', {}, [
					block( 'core/paragraph', {
						content: 'Nested inside a Group block.',
					} ),
				] ),
			],
		} );

		expect( summary ).toBe( 'Nested inside a Group block.' );
	} );

	it( 'reads a list built from list-item inner blocks, keeping items apart', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: '',
			blocks: [
				block( 'core/list', {}, [
					block( 'core/list-item', { content: 'First item' } ),
					block( 'core/list-item', { content: 'Second item' } ),
				] ),
			],
		} );

		expect( summary ).toBe( 'First item\n\nSecond item' );
	} );

	it( 'reads a legacy flat Quote block from its value attribute', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: '',
			blocks: [
				block( 'core/quote', {
					value: '<p>A happy customer.</p>',
				} ),
			],
		} );

		expect( summary ).toBe( 'A happy customer.' );
	} );

	it( 'reads a quote’s citation separately from its quoted text', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: '',
			blocks: [
				block( 'core/quote', {
					value: '<p>Best decision for our business.</p>',
					citation: 'Jane Doe, Happy Customer',
				} ),
			],
		} );

		expect( summary ).toBe(
			'Best decision for our business.\n\nJane Doe, Happy Customer'
		);
	} );

	it( "reads a details block's own summary heading, separately from its expandable body", () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: '',
			blocks: [
				block( 'core/details', { summary: 'Returns Policy' }, [
					block( 'core/paragraph', {
						content: 'Returns accepted within 30 days.',
					} ),
				] ),
			],
		} );

		expect( summary ).toBe(
			'Returns Policy\n\nReturns accepted within 30 days.'
		);
	} );

	it( 'reads a table one cell at a time, instead of running every cell together', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: '',
			blocks: [
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
			],
		} );

		expect( summary ).toBe( 'Day\n\nHours\n\nMon\n\n9–5' );
	} );

	it( "reads a table's caption after its cells", () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: '',
			blocks: [
				block( 'core/table', {
					body: [ { cells: [ { content: 'Mon', tag: 'td' } ] } ],
					caption: 'Store hours',
				} ),
			],
		} );

		expect( summary ).toBe( 'Mon\n\nStore hours' );
	} );

	it( 'strips HTML from a block’s content', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: '',
			blocks: [
				block( 'core/paragraph', {
					content: '<strong>Bold</strong> and <em>italic</em> text.',
				} ),
			],
		} );

		expect( summary ).toBe( 'Bold and italic text.' );
	} );

	it( 'returns just the title when there are no text-bearing blocks', () => {
		const summary = buildSummaryFromLiveBlocks( {
			title: 'Gallery',
			blocks: [ block( 'core/image', {} ) ],
		} );

		expect( summary ).toBe( 'Gallery' );
	} );

	it( 'returns an empty string when there is no title and no text-bearing blocks', () => {
		const summary = buildSummaryFromLiveBlocks( { title: '', blocks: [] } );

		expect( summary ).toBe( '' );
	} );
} );
