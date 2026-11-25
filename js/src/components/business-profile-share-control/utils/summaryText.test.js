/**
 * Internal dependencies
 */
import {
	buildSummary,
	countSummaryCharacters,
	truncateSummary,
} from './summaryText';

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

describe( 'buildSummary', () => {
	it( 'joins the title and content with a blank line', () => {
		expect( buildSummary( 'Summer sale', 'Up to 50% off.' ) ).toBe(
			'Summer sale\n\nUp to 50% off.'
		);
	} );

	it( 'drops a blank title', () => {
		expect( buildSummary( '  ', 'Up to 50% off.' ) ).toBe(
			'Up to 50% off.'
		);
	} );

	it( 'decodes HTML entities in the title', () => {
		expect(
			buildSummary( 'Trail Run &amp; Gear Sale', 'Up to 50% off.' )
		).toBe( 'Trail Run & Gear Sale\n\nUp to 50% off.' );
	} );

	it( 'drops blank content', () => {
		expect( buildSummary( 'Summer sale', '  ' ) ).toBe( 'Summer sale' );
	} );

	it( 'truncates the combined text at the character limit', () => {
		const content = 'x'.repeat( 1500 );

		expect( buildSummary( 'Title', content ) ).toBe(
			`Title\n\n${ content }`.slice( 0, 1500 )
		);
	} );
} );
