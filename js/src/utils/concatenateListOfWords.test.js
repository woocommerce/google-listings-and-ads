/**
 * Internal dependencies
 */
import concatenateListOfWords from './concatenateListOfWords';

describe( 'concatenateListOfWords', () => {
	it( 'One words with default separators', () => {
		const listOfWords = [ 'wordA' ];
		expect( concatenateListOfWords( listOfWords ) ).toBe( 'wordA' );
	} );

	it( 'Multiple words with default separators', () => {
		const listOfWords = [ 'wordA', 'wordB', 'wordC' ];
		expect( concatenateListOfWords( listOfWords ) ).toBe(
			'wordA, wordB and wordC'
		);
	} );
	it( 'Multiple words with custom separators', () => {
		const listOfWords = [ 'wordA', 'wordB', 'wordC' ];
		expect( concatenateListOfWords( listOfWords, ' - ', '&' ) ).toBe(
			'wordA - wordB & wordC'
		);
	} );
} );
