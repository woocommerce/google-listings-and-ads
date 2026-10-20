/**
 * Internal dependencies
 */
import { SUMMARY_MAX_LENGTH } from '../constants';

/**
 * Counts the characters in a text the way Google Business Profile does: one per Unicode
 * character, so an emoji counts once.
 *
 * @param {string} text The text to count.
 * @return {number} The number of characters.
 */
export function countSummaryCharacters( text ) {
	return Array.from( text ).length;
}

/**
 * Cuts a text at the Google Business Profile character limit.
 *
 * @param {string} text The text to cut.
 * @return {string} The text, at most `SUMMARY_MAX_LENGTH` characters long.
 */
export function truncateSummary( text ) {
	return Array.from( text ).slice( 0, SUMMARY_MAX_LENGTH ).join( '' );
}
