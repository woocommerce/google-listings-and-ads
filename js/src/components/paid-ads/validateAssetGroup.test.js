/**
 * Internal dependencies
 */
import validateAssetGroup from './validateAssetGroup';
import {
	ASSET_IMAGE_SPECS,
	ASSET_TEXT_SPECS,
	ASSET_DISPLAY_URL_PATH_SPECS,
} from './assetSpecs';

/**
 * `validateAssetGroup` function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 */
describe( 'validateAssetGroup', () => {
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
			marketing_image: [ 'https://image_1' ],
			square_marketing_image: [ 'https://image_1' ],
			logo: [ 'https://logo_1' ],
			headline: [ 'headline 1', 'headline 2', 'headline 3' ],
			long_headline: [ 'long_headline 1' ],
			description: [ 'description 1', 'description 2' ],
			display_url_path: [ 'foo', 'bar' ],
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
				values[ key ] = Array.from( { length: min - 1 }, toUrl );
				const error = validateAssetGroup( values );

				expect( error ).toHaveProperty( key );
				expect( error[ key ] ).toMatchSnapshot();
			}
		);
	} );

	describe( 'Text assets', () => {
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
				texts[ 0 ] = '';
				texts[ 1 ] = '';
				values[ key ] = texts;
				const error = validateAssetGroup( values );

				expect( error ).not.toHaveProperty( key );
			}
		);

		it.each( ASSET_TEXT_SPECS.map( ( spec ) => [ spec.key, spec ] ) )(
			'When the character limit is exceeded in values.%s, it should not pass',
			( key, spec ) => {
				values[ key ] = Array.from( { length: spec.min }, ( _, i ) => {
					const limit =
						spec.maxCharacterCounts?.[ i ] ??
						spec.maxCharacterCounts;
					return i.toString().repeat( limit + 1 );
				} );
				const error = validateAssetGroup( values );

				expect( error ).toHaveProperty( key );
				expect( Array.isArray( error[ key ] ) ).toBe( true );
				expect( error[ key ] ).toHaveLength( spec.min );
				expect( error[ key ] ).toMatchSnapshot();
			}
		);
	} );

	describe( 'Display URL paths asset', () => {
		it( 'When display_url_path is an empty array or an array with all empty string elements, it should pass', () => {
			values.display_url_path = [];

			expect( validateAssetGroup( values ) ).toStrictEqual( {} );

			values.display_url_path = [ '', '' ];

			expect( validateAssetGroup( values ) ).toStrictEqual( {} );
		} );

		it( 'When display_url_path is an array where the first element is an empty string and the second is a non-empty string, it should not pass', () => {
			values.display_url_path = [ '', 'bar' ];
			const error = validateAssetGroup( values );

			expect( error ).toHaveProperty( 'display_url_path' );
			expect( Array.isArray( error.display_url_path ) ).toBe( true );
			expect( error.display_url_path ).toHaveLength( 1 );
			expect( error.display_url_path ).toMatchSnapshot();
		} );

		it( 'When the character limit is exceeded in display_url_path, it should not pass', () => {
			const specs = ASSET_DISPLAY_URL_PATH_SPECS;
			const { length } = specs;
			values.display_url_path = Array.from( { length }, ( _, i ) => {
				const limit = specs[ i ].maxCharacterCount;
				return i.toString().repeat( limit + 1 );
			} );
			const error = validateAssetGroup( values );

			expect( error ).toHaveProperty( 'display_url_path' );
			expect( Array.isArray( error.display_url_path ) ).toBe( true );
			expect( error.display_url_path ).toHaveLength( length );
			expect( error.display_url_path ).toMatchSnapshot();
		} );
	} );
} );
