/**
 * Internal dependencies
 */
import getValidCurrencyCodes from './getValidCurrencyCodes';

const CURRENCIES = [
	{ code: 'USD', symbol: '$', languages: [ 'en', 'fr' ] },
	{ code: 'EUR', symbol: '€', languages: [ 'en', 'fr' ] },
	{ code: 'AED', symbol: 'د.إ', languages: [ 'fr' ] },
	{ code: 'GBP', symbol: '£', languages: [ 'de' ] },
];

describe( 'getValidCurrencyCodes', () => {
	test( 'returns every currency code when no language is selected', () => {
		expect( Array.from( getValidCurrencyCodes( CURRENCIES, [] ) ) ).toEqual(
			[ 'USD', 'EUR', 'AED', 'GBP' ]
		);
		expect(
			Array.from( getValidCurrencyCodes( CURRENCIES, undefined ) )
		).toEqual( [ 'USD', 'EUR', 'AED', 'GBP' ] );
	} );

	test( 'excludes currencies not enabled for the selected language', () => {
		expect(
			Array.from( getValidCurrencyCodes( CURRENCIES, [ 'fr' ] ) )
		).toEqual( [ 'USD', 'EUR', 'AED' ] );
	} );

	test( 'includes currencies valid for any of the selected languages (union)', () => {
		expect(
			Array.from( getValidCurrencyCodes( CURRENCIES, [ 'fr', 'de' ] ) )
		).toEqual( [ 'USD', 'EUR', 'AED', 'GBP' ] );
	} );

	test( 'returns an empty array when no currency matches the selected language', () => {
		expect(
			Array.from( getValidCurrencyCodes( CURRENCIES, [ 'es' ] ) )
		).toEqual( [] );
	} );

	test( 'treats a missing currencies list as empty', () => {
		expect(
			Array.from( getValidCurrencyCodes( undefined, [ 'fr' ] ) )
		).toEqual( [] );
	} );
} );
