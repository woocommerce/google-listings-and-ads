/**
 * Internal dependencies
 */
import {
	countSummaryCharacters,
	truncateSummary,
} from './buildSummaryFromPost';

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
