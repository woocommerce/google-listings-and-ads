/**
 * A function that joins a list of words using the sepators provided as the second and third argument.
 *
 * Example:
 *
 * concatenateListOfWords(['A', 'B', 'C']) will return 'A, B and C'
 *
 * @param  {Array<string>} listWords A list of words to concatenate.
 * @param  {string} [separator] The separator between words.
 * @param  {string} [separatorLastElement] The separator for the last word.
 * @return {string} The concatenated string.
 */

export default function concatenateListOfWords(
	listWords,
	separator = ', ',
	separatorLastElement = 'and'
) {
	if ( listWords.length === 1 ) {
		return listWords.slice( -1 ).join( '' );
	}
	return `${ listWords
		.slice( 0, -1 )
		.join( separator ) } ${ separatorLastElement } ${ listWords.slice(
		-1
	) }`;
}
