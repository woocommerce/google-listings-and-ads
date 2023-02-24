/**
 * Internal dependencies
 */
import validateAssetGroup from './validateAssetGroup';
import {
	ASSET_IMAGE_SPECS,
	ASSET_TEXT_SPECS,
	ASSET_DISPLAY_URL_PATH_SPECS,
} from './assetSpecs';
import { ASSET_FORM_KEY } from '.~/constants';

/**
 * `validateAssetGroup` function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 */
describe( 'validateAssetGroup', () => {
	const charTable = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	let values;

	function toUrl( _, index ) {
		return `https://url_${ index }`;
	}

	function toText( _, index ) {
		return `text-${ index }`;
	}

	beforeEach( () => {
		// Valid values
		values = {
			[ ASSET_FORM_KEY.MARKETING_IMAGE ]: [ 'https://image_1' ],
			[ ASSET_FORM_KEY.SQUARE_MARKETING_IMAGE ]: [ 'https://image_1' ],
			[ ASSET_FORM_KEY.PORTRAIT_MARKETING_IMAGE ]: [ 'https://image_1' ],
			[ ASSET_FORM_KEY.LOGO ]: [ 'https://logo_1' ],
			[ ASSET_FORM_KEY.HEADLINE ]: [
				'headline 1',
				'headline 2',
				'headline 3',
			],
			[ ASSET_FORM_KEY.LONG_HEADLINE ]: [ 'long_headline 1' ],
			[ ASSET_FORM_KEY.DESCRIPTION ]: [
				'description 1',
				'description 2',
			],
			[ ASSET_FORM_KEY.DISPLAY_URL_PATH ]: [ 'foo', 'bar' ],
		};
	} );

	it( 'When all checks are passed, should return an empty object', () => {
		const error = validateAssetGroup( values );

		expect( error ).toStrictEqual( {} );
	} );

	describe( 'Image assets', () => {
		it.each( ASSET_IMAGE_SPECS.map( ( spec ) => [ spec.key, spec.min ] ) )(
			'When the length of values.%s is less than %s, it should not pass',
			( key, min ) => {
				// It's impossible to get an array with a negative length, so this test doesn't need to cover this case.
				if ( min === 0 ) {
					return;
				}

				values[ key ] = Array.from( { length: min - 1 }, toUrl );
				const error = validateAssetGroup( values );

				expect( error ).toHaveProperty( key );
				expect( error[ key ] ).toMatchSnapshot();
			}
		);
	} );

	describe( 'Text assets', () => {
		it.each(
			ASSET_TEXT_SPECS.filter( ( spec ) => {
				return (
					spec.min >= 2 && Array.isArray( spec.maxCharacterCounts )
				);
			} ).map( ( spec ) => [ spec.key, spec ] )
		)(
			'When the first value of %s is an empty string, it should not pass',
			( key, spec ) => {
				const texts = Array.from( { length: spec.min + 1 }, toText );
				texts[ 0 ] = '';
				values[ key ] = texts;
				const error = validateAssetGroup( values );

				expect( error ).toHaveProperty( key );
				expect( Array.isArray( error[ key ] ) ).toBe( true );
				expect( error[ key ] ).toHaveLength( 1 );
				expect( error[ key ] ).toMatchSnapshot();
			}
		);

		it.each( ASSET_TEXT_SPECS.map( ( spec ) => [ spec.key, spec.min ] ) )(
			'When the length of values.%s is less than %s after omitting empty strings, it should not pass',
			( key, min ) => {
				const texts = Array.from( { length: min - 1 }, toText );
				texts.push( '' );
				values[ key ] = texts;
				const error = validateAssetGroup( values );

				expect( error ).toHaveProperty( key );
				expect( Array.isArray( error[ key ] ) ).toBe( true );
				expect( error[ key ] ).toHaveLength( 1 );
				expect( error[ key ] ).toMatchSnapshot();
			}
		);

		it.each( ASSET_TEXT_SPECS.map( ( spec ) => [ spec.key, spec ] ) )(
			'When there is duplication in values.%s, it should not pass',
			( key, spec ) => {
				const texts = Array.from( { length: spec.min + 1 }, toText );
				texts[ 0 ] = 'same-text';
				texts[ 1 ] = 'same-text';
				values[ key ] = texts;
				const error = validateAssetGroup( values );

				expect( error ).toHaveProperty( key );
				expect( Array.isArray( error[ key ] ) ).toBe( true );
				expect( error[ key ] ).toHaveLength( 1 );
				expect( error[ key ] ).toMatchSnapshot();
			}
		);

		it.each( ASSET_TEXT_SPECS.map( ( spec ) => [ spec.key, spec ] ) )(
			'When checking duplication, it should ignore empty strings',
			( key, spec ) => {
				const texts = Array.from( { length: spec.min + 2 }, toText );
				texts[ 1 ] = '';
				texts[ 2 ] = '';
				values[ key ] = texts;
				const error = validateAssetGroup( values );

				expect( error ).not.toHaveProperty( key );
			}
		);

		it.each( ASSET_TEXT_SPECS.map( ( spec ) => [ spec.key, spec ] ) )(
			'When the character limit is exceeded in values.%s, it should not pass',
			( key, spec ) => {
				const length = spec.min;
				const exceededStrings = Array.from( { length }, ( _, i ) => {
					const limit =
						spec.maxCharacterCounts?.[ i ] ??
						spec.maxCharacterCounts;
					const exceededCount = limit + 1;
					return charTable.charAt( i ).repeat( exceededCount );
				} );
				values[ key ] = exceededStrings;
				const error = validateAssetGroup( values );

				expect( error ).toHaveProperty( key );
				expect( Array.isArray( error[ key ] ) ).toBe( true );
				expect( error[ key ] ).toHaveLength( length );
				expect( error[ key ] ).toMatchSnapshot();
			}
		);
	} );

	describe( 'Display URL paths asset', () => {
		it( `When values.${ ASSET_FORM_KEY.DISPLAY_URL_PATH } is an empty array or an array with all empty string elements, it should pass`, () => {
			values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ] = [];

			expect( validateAssetGroup( values ) ).toStrictEqual( {} );

			values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ] = [ '', '' ];

			expect( validateAssetGroup( values ) ).toStrictEqual( {} );
		} );

		it( `When values.${ ASSET_FORM_KEY.DISPLAY_URL_PATH } is an array where the first element is an empty string and the second is a non-empty string, it should not pass`, () => {
			values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ] = [ '', 'bar' ];
			const error = validateAssetGroup( values );
			const messages = error[ ASSET_FORM_KEY.DISPLAY_URL_PATH ];

			expect( error ).toHaveProperty( ASSET_FORM_KEY.DISPLAY_URL_PATH );
			expect( Array.isArray( messages ) ).toBe( true );
			expect( messages ).toHaveLength( 1 );
			expect( messages ).toMatchSnapshot();
		} );

		it( `When the character limit is exceeded in values.${ ASSET_FORM_KEY.DISPLAY_URL_PATH }, it should not pass`, () => {
			const specs = ASSET_DISPLAY_URL_PATH_SPECS;
			const exceededStrings = specs.map( ( spec, i ) => {
				const exceededCount = spec.maxCharacterCount + 1;
				return charTable.charAt( i ).repeat( exceededCount );
			} );
			values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ] = exceededStrings;
			const error = validateAssetGroup( values );
			const messages = error[ ASSET_FORM_KEY.DISPLAY_URL_PATH ];

			expect( error ).toHaveProperty( ASSET_FORM_KEY.DISPLAY_URL_PATH );
			expect( Array.isArray( messages ) ).toBe( true );
			expect( messages ).toHaveLength( specs.length );
			expect( messages ).toMatchSnapshot();
		} );
	} );
} );
