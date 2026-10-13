/**
 * Internal dependencies
 */
import buildSummaryFromPost, {
	countSummaryCharacters,
	truncateSummary,
} from './buildSummaryFromPost';

describe( 'buildSummaryFromPost', () => {
	it( 'uses the excerpt alone, as plain text, when the merchant wrote one', () => {
		expect(
			buildSummaryFromPost( {
				title: 'New summer collection just landed',
				hasManualExcerpt: true,
				renderedExcerpt:
					'<p>Our summer collection is in store now &amp; online.</p>\n',
			} )
		).toBe( 'Our summer collection is in store now & online.' );
	} );

	it( 'uses the title followed by the derived excerpt when there is no excerpt', () => {
		expect(
			buildSummaryFromPost( {
				title: 'New summer collection just landed',
				hasManualExcerpt: false,
				renderedExcerpt:
					'<p>Our summer range is now in store. Lighter fabrics, fresh colours.</p>\n',
			} )
		).toBe(
			'New summer collection just landed\nOur summer range is now in store. Lighter fabrics, fresh colours.'
		);
	} );

	it( 'fills whatever text is available when the post has no content', () => {
		expect(
			buildSummaryFromPost( {
				title: 'Gallery',
				hasManualExcerpt: false,
				renderedExcerpt: '',
			} )
		).toBe( 'Gallery' );
		expect(
			buildSummaryFromPost( {
				title: '',
				hasManualExcerpt: false,
				renderedExcerpt: '',
			} )
		).toBe( '' );
	} );

	it( 'cuts the text at the character limit', () => {
		const summary = buildSummaryFromPost( {
			title: '',
			hasManualExcerpt: true,
			renderedExcerpt: `<p>${ 'a'.repeat( 1600 ) }</p>`,
		} );

		expect( summary ).toHaveLength( 1500 );
	} );
} );

describe( 'truncateSummary', () => {
	it( 'keeps text within the limit unchanged', () => {
		expect( truncateSummary( 'Hello' ) ).toBe( 'Hello' );
	} );

	it( 'counts and cuts by character, so an emoji is never split', () => {
		const text = '😀'.repeat( 1501 );

		expect( countSummaryCharacters( text ) ).toBe( 1501 );
		expect( countSummaryCharacters( truncateSummary( text ) ) ).toBe(
			1500
		);
		expect( truncateSummary( text ) ).toBe( '😀'.repeat( 1500 ) );
	} );
} );
