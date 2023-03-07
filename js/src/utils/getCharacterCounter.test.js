/**
 * Internal dependencies
 */
import getCharacterCounter, {
	getCharacterCounterOfGoogleAds,
} from './getCharacterCounter';

describe( 'getCharacterCounter', () => {
	it( 'Should return the specified character counter function by the given kind.', () => {
		expect( getCharacterCounter( 'google-ads' ) ).toEqual(
			expect.any( Function )
		);
	} );

	it( 'Should throw error if the given `kind` is unknown.', () => {
		expect( () => getCharacterCounter( 'foobar' ) ).toThrow();
	} );
} );

describe( 'getCharacterCounterOfGoogleAds', () => {
	function prevChar( char ) {
		return String.fromCharCode( char.charCodeAt( 0 ) - 1 );
	}

	function nextChar( char ) {
		return String.fromCharCode( char.charCodeAt( 0 ) + 1 );
	}

	const count = getCharacterCounterOfGoogleAds();

	describe( 'Boundary tests', () => {
		test( 'Boundary between Basic Latin and Cyrillic', () => {
			expect( count( '\u0000' ) ).toBe( 1 );
			expect( count( '\u04F9' ) ).toBe( 1 );
			expect( count( nextChar( '\u04F9' ) ) ).toBe( 2 );
		} );

		test( 'Boundary between Latin Extended Additional and Currency Symbols', () => {
			expect( count( '\u1E00' ) ).toBe( 1 );
			expect( count( '\u20BF' ) ).toBe( 1 );
			expect( count( prevChar( '\u1E00' ) ) ).toBe( 2 );
			expect( count( nextChar( '\u20BF' ) ) ).toBe( 2 );
		} );

		test( 'Boundary of Halfwidth and Fullwidth Forms', () => {
			expect( count( '\uFF61' ) ).toBe( 1 );
			expect( count( '\uFFDC' ) ).toBe( 1 );
			expect( count( prevChar( '\uFF61' ) ) ).toBe( 2 );
			expect( count( nextChar( '\uFFDC' ) ) ).toBe( 2 );
		} );

		test( 'Boundary of Thai', () => {
			expect( count( '\u0E00' ) ).toBe( 1 );
			expect( count( '\u0E7F' ) ).toBe( 1 );
			expect( count( prevChar( '\u0E00' ) ) ).toBe( 2 );
			expect( count( nextChar( '\u0E7F' ) ) ).toBe( 2 );
		} );

		test( 'Boundary of Letterlike Symbols', () => {
			expect( count( '\u2100' ) ).toBe( 1 );
			expect( count( '\u213A' ) ).toBe( 1 );
			expect( count( prevChar( '\u2100' ) ) ).toBe( 2 );
			expect( count( nextChar( '\u213A' ) ) ).toBe( 2 );
		} );

		test( 'Boundary of Arabic', () => {
			expect( count( '\u0600' ) ).toBe( 1 );
			expect( count( '\u06FF' ) ).toBe( 1 );
			expect( count( prevChar( '\u0600' ) ) ).toBe( 2 );
			expect( count( nextChar( '\u06FF' ) ) ).toBe( 2 );
		} );

		test( 'Boundary of Arabic Supplement', () => {
			expect( count( '\u0750' ) ).toBe( 1 );
			expect( count( '\u077F' ) ).toBe( 1 );
			expect( count( prevChar( '\u0750' ) ) ).toBe( 2 );
			expect( count( nextChar( '\u077F' ) ) ).toBe( 2 );
		} );

		test( 'Boundary of Arabic Presentation Forms-A', () => {
			expect( count( '\uFB50' ) ).toBe( 1 );
			expect( count( '\uFDFF' ) ).toBe( 1 );
			expect( count( prevChar( '\uFB50' ) ) ).toBe( 2 );
			expect( count( nextChar( '\uFDFF' ) ) ).toBe( 2 );
		} );

		test( 'Boundary of Arabic Presentation Forms-B', () => {
			expect( count( '\uFE70' ) ).toBe( 1 );
			expect( count( '\uFEFF' ) ).toBe( 1 );
			expect( count( prevChar( '\uFE70' ) ) ).toBe( 2 );
			expect( count( nextChar( '\uFEFF' ) ) ).toBe( 2 );
		} );

		test( 'Boundary of Hebrew', () => {
			expect( count( '\u05D0' ) ).toBe( 1 );
			expect( count( '\u05EA' ) ).toBe( 1 );
			expect( count( prevChar( '\u05D0' ) ) ).toBe( 2 );
			expect( count( nextChar( '\u05EA' ) ) ).toBe( 2 );
		} );

		test( 'Boundary between Devanagari and Malayalam', () => {
			expect( count( '\u0900' ) ).toBe( 0 );
			expect( count( '\u0D7F' ) ).toBe( 1 );
			expect( count( prevChar( '\u0900' ) ) ).toBe( 2 );
			expect( count( nextChar( '\u0D7F' ) ) ).toBe( 2 );
		} );
	} );

	describe( 'Special cases', () => {
		it( 'Should count some Hebrew punctuation chars as 1', () => {
			expect( count( '\u05BE' ) ).toBe( 1 );
			expect( count( '\u05F3' ) ).toBe( 1 );
			expect( count( '\u05F4' ) ).toBe( 1 );
		} );

		it( 'Should count some specific chars in Devanagari as 0', () => {
			expect( count( '\u0900\u0901\u0902\u093A\u093C' ) ).toBe( 0 );
			expect( count( '\u0941\u0942\u0943\u0944\u0945' ) ).toBe( 0 );
			expect( count( '\u0946\u0947\u0948\u094D\u0951' ) ).toBe( 0 );
			expect( count( '\u0952\u0953\u0954\u0955\u0956' ) ).toBe( 0 );
			expect( count( '\u0957\u0962\u0963' ) ).toBe( 0 );
		} );
	} );

	describe( 'Writing systems', () => {
		test( 'English', () => {
			const greeting = 'Hello from the children of planet Earth';
			expect( count( greeting ) ).toBe( 39 );
		} );

		test( 'Chinese', () => {
			const greeting = '太空朋友，恁好！恁食飽未？有閒著來阮遮坐喔。';
			expect( count( greeting ) ).toBe( 44 );
		} );

		test( 'Japanese', () => {
			expect( count( 'こんにちは。お元気ですか？' ) ).toBe( 26 );
			// Halfwidth kana (半角カナ)
			expect( count( 'ｺﾝﾆﾁﾊ｡ｵ元気ﾃﾞｽｶ?' ) ).toBe( 16 );
		} );

		test( 'Korean', () => {
			expect( count( '안녕하세요' ) ).toBe( 10 );
		} );

		test( 'Hindi', () => {
			expect( count( 'धरती के वासियों की ओर से नमस्कार' ) ).toBe( 28 );
		} );

		test( 'Greek', () => {
			const greeting = `Οἵτινές ποτ'ἔστε χαίρετε! Εἰρηνικῶς πρὸς φίλους ἐληλύθαμεν φίλοι.`;
			expect( count( greeting ) ).toBe( 65 );
		} );

		test( 'Thai', () => {
			const greeting =
				'สวัสดีค่ะ สหายในธรณีโพ้น พวกเราในธรณีนี้ขอส่งมิตรจิตมาถึงท่านทุกคน';
			expect( count( greeting ) ).toBe( 66 );
		} );

		test( 'Polish', () => {
			expect( count( 'Witajcie, istoty z zaświatów.' ) ).toBe( 29 );
		} );

		test( 'Arabic', () => {
			const greeting =
				'.تحياتنا للأصدقاء في النجوم. يا ليت يجمعنا الزمان';
			expect( count( greeting ) ).toBe( 49 );
		} );

		test( 'Hebrew', () => {
			expect( count( 'שלום' ) ).toBe( 4 );
		} );

		test( 'Devanagari', () => {
			expect( count( 'देवनागरी' ) ).toBe( 7 );
			expect( count( 'संस्कृता' ) ).toBe( 5 );
		} );

		test( 'Burmese', () => {
			expect( count( 'နေကောင်းပါသလား' ) ).toBe( 28 );
		} );
	} );

	describe( 'Symbols and miscellaneous cases that have no consistency', () => {
		test( 'Emoji', () => {
			expect( count( '✌' ) ).toBe( 2 );
			expect( count( '✌🏿' ) ).toBe( 6 );
			expect( count( '👍' ) ).toBe( 4 );
			expect( count( '👍🏻' ) ).toBe( 8 );
			expect( count( '😮‍💨' ) ).toBe( 9 );
			expect( count( '👨‍👩‍👧‍👦' ) ).toBe( 19 );
			expect( count( '🧑🏻‍❤️‍💋‍🧑🏼' ) ).toBe( 27 );
		} );

		test( 'Enumeration of symbols without consistency rules', () => {
			// The diaeresis of Latin letters could be a combining diaeresis.
			expect( count( 'ä' ) ).toBe( 1 );
			expect( count( 'ä' ) ).toBe( 2 );

			// Most Cyrillic chars are counted as 1 but a few as 2.
			expect( count( 'ҳ' ) ).toBe( 1 );
			expect( count( 'ӽ' ) ).toBe( 2 );
			expect( count( 'Ӻ' ) ).toBe( 2 );
			expect( count( 'ӿ' ) ).toBe( 2 );

			// Inconsistencies in Hebrew punctuations.
			expect( count( '״' ) ).toBe( 1 );
			expect( count( '׃' ) ).toBe( 2 );

			// Inconsistencies in Letterlike Symbols.
			expect( count( '℡' ) ).toBe( 1 );
			expect( count( '℻' ) ).toBe( 2 );
			expect( count( '℁' ) ).toBe( 1 );
			expect( count( '⅍' ) ).toBe( 2 );

			// Inconsistencies in Halfwidth and Fullwidth Forms.
			// - Halfwidth CJK punctuation
			expect( count( '｢' ) ).toBe( 1 );
			// - Halfwidth symbol variants
			expect( count( '￪' ) ).toBe( 2 );
			expect( count( '￮' ) ).toBe( 2 );

			// CJK-related blocks are not fully included such as the CJK compatibility letters and Hangul syllables.
			expect( count( '城' ) ).toBe( 2 );
			expect( count( '城' ) ).toBe( 4 );
			expect( count( '각' ) ).toBe( 2 );
			expect( count( '각' ) ).toBe( 6 );

			// Special symbols or ligatures.
			expect( count( '﷽' ) ).toBe( 1 );
			expect( count( '꧅' ) ).toBe( 2 );
			expect( count( '𒐫' ) ).toBe( 4 );
			expect( count( 'Æ' ) ).toBe( 1 );
			expect( count( 'Ꜳ' ) ).toBe( 2 );
			expect( count( '🜇' ) ).toBe( 4 );
			expect( count( 'æ' ) ).toBe( 1 );
			expect( count( 'ꜽ' ) ).toBe( 2 );
		} );
	} );
} );
