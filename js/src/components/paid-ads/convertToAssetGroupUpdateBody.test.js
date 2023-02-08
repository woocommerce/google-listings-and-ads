/**
 * External dependencies
 */
import { uniqueId } from 'lodash';

/**
 * Internal dependencies
 */
import convertToAssetGroupUpdateBody, {
	diffAssetOperations,
} from './convertToAssetGroupUpdateBody';
import { ASSET_KEY, ASSET_GROUP_KEY, ASSET_FORM_KEY } from '.~/constants';
import { structuredClone } from '.~/utils/structuredClone.js';

function genId() {
	return Number( uniqueId() );
}

describe( 'convertToAssetGroupUpdateBody', () => {
	let assetGroup;
	let values;

	beforeEach( () => {
		assetGroup = {
			[ ASSET_GROUP_KEY.FINAL_URL ]: 'https://example/cap',
			[ ASSET_GROUP_KEY.DISPLAY_URL_PATH ]: [ 'cap', 'specials' ],
			assets: {
				[ ASSET_KEY.BUSINESS_NAME ]: {
					id: genId(),
					content: 'General Clothing',
				},
				[ ASSET_KEY.HEADLINE ]: [
					{
						id: genId(),
						content: 'headline 1',
					},
				],
				[ ASSET_KEY.LOGO ]: [
					{
						id: genId(),
						content: 'https://example/logo-1.png',
					},
				],
			},
		};

		values = {
			[ ASSET_FORM_KEY.FINAL_URL ]: 'https://example',
			[ ASSET_FORM_KEY.DISPLAY_URL_PATH ]: [ 'cap', 'specials' ],
			[ ASSET_FORM_KEY.BUSINESS_NAME ]: 'General Clothing',
			[ ASSET_FORM_KEY.HEADLINE ]: [ 'headline 1' ],
			[ ASSET_FORM_KEY.LOGO ]: [ 'https://example/logo-1.png' ],
		};
	} );

	describe( 'should base on an existing asset entity group and convert the assets form values into the request body for updating that existing asset group', () => {
		test( 'the returned data should meet the required structure', () => {
			values[ ASSET_FORM_KEY.FINAL_URL ] = 'https://example/belt';
			values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ] = [ 'belt', 'specials' ];

			const body = convertToAssetGroupUpdateBody( assetGroup, values );

			expect( body ).toHaveProperty( 'final_url' );
			expect( body ).toHaveProperty( 'path1' );
			expect( body ).toHaveProperty( 'path2' );
			expect( body ).toHaveProperty( 'assets' );
		} );

		test( 'the returned data should have the final URL and display URL paths taken from the form values', () => {
			values[ ASSET_FORM_KEY.FINAL_URL ] = 'https://example/belt';
			values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ] = [ 'belt', 'specials' ];

			const body = convertToAssetGroupUpdateBody( assetGroup, values );
			const formValuePaths = values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ];

			expect( body.final_url ).toBe( values[ ASSET_FORM_KEY.FINAL_URL ] );
			expect( body.path1 ).toBe( formValuePaths[ 0 ] );
			expect( body.path2 ).toBe( formValuePaths[ 1 ] );
		} );

		test( 'when assets are not changed, the `assets` in the returned data should be an empty array', () => {
			const body = convertToAssetGroupUpdateBody( assetGroup, values );

			expect( body.assets ).toEqual( [] );
		} );

		test( 'when assets are changed, the `assets` in the returned data should have the corresponding operations that meet the required structure', () => {
			values[ ASSET_FORM_KEY.BUSINESS_NAME ] = 'Better Clothing';
			values[ ASSET_FORM_KEY.HEADLINE ] = [ 'new headline' ];
			values[ ASSET_FORM_KEY.LOGO ] = [ 'https://example/logo-2.png' ];

			const body = convertToAssetGroupUpdateBody( assetGroup, values );

			[
				ASSET_FORM_KEY.BUSINESS_NAME,
				ASSET_FORM_KEY.HEADLINE,
				ASSET_FORM_KEY.LOGO,
			].forEach( ( key ) => {
				const operations = body.assets.filter(
					( operation ) => operation.field_type === key
				);
				const asset = assetGroup.assets[ key ];
				const value = values[ key ];

				expect( operations ).toContainEqual( {
					id: asset.id || asset[ 0 ].id,
					content: null,
					field_type: key,
				} );
				expect( operations ).toContainEqual( {
					id: null,
					content: Array.isArray( value ) ? value[ 0 ] : value,
					field_type: key,
				} );
			} );
		} );
	} );
} );

describe( 'diffAssetOperations', () => {
	const filledAssetGroup = {
		[ ASSET_GROUP_KEY.FINAL_URL ]: 'https://example',
		[ ASSET_GROUP_KEY.DISPLAY_URL_PATH ]: [ 'cap', 'specials' ],
		assets: {
			[ ASSET_KEY.BUSINESS_NAME ]: {
				id: genId(),
				content: 'General Clothing',
			},
			[ ASSET_KEY.MARKETING_IMAGE ]: [
				{
					id: genId(),
					content: 'https://example/image-1.png',
				},
			],
			[ ASSET_KEY.SQUARE_MARKETING_IMAGE ]: [
				{
					id: genId(),
					content: 'https://example/square-image-1.png',
				},
			],
			[ ASSET_KEY.PORTRAIT_MARKETING_IMAGE ]: [
				{
					id: genId(),
					content: 'https://example/portrait-image-1.png',
				},
			],
			[ ASSET_KEY.LOGO ]: [
				{
					id: genId(),
					content: 'https://example/logo-1.png',
				},
				{
					id: genId(),
					content: 'https://example/logo-2.png',
				},
				{
					id: genId(),
					content: 'https://example/logo-3.png',
				},
			],
			[ ASSET_KEY.HEADLINE ]: [
				{
					id: genId(),
					content: 'headline 1',
				},
				{
					id: genId(),
					content: 'headline 2',
				},
				{
					id: genId(),
					content: 'headline 3',
				},
			],
			[ ASSET_KEY.LONG_HEADLINE ]: [
				{
					id: genId(),
					content: 'long headline 1',
				},
			],
			[ ASSET_KEY.DESCRIPTION ]: [
				{
					id: genId(),
					content: 'description 1',
				},
			],
			[ ASSET_KEY.CALL_TO_ACTION_SELECTION ]: {
				id: genId(),
				content: 'sign_up',
			},
		},
	};

	const filledValues = {
		[ ASSET_FORM_KEY.BUSINESS_NAME ]: 'General Clothing',
		[ ASSET_FORM_KEY.MARKETING_IMAGE ]: [ 'https://example/image-1.png' ],
		[ ASSET_FORM_KEY.SQUARE_MARKETING_IMAGE ]: [
			'https://example/square-image-1.png',
		],
		[ ASSET_FORM_KEY.PORTRAIT_MARKETING_IMAGE ]: [
			'https://example/portrait-image-1.png',
		],
		[ ASSET_FORM_KEY.LOGO ]: [
			'https://example/logo-1.png',
			'https://example/logo-2.png',
			'https://example/logo-3.png',
		],
		[ ASSET_FORM_KEY.HEADLINE ]: [
			'headline 1',
			'headline 2',
			'headline 3',
		],
		[ ASSET_FORM_KEY.LONG_HEADLINE ]: [ 'long headline 1' ],
		[ ASSET_FORM_KEY.DESCRIPTION ]: [ 'description 1' ],
		[ ASSET_FORM_KEY.CALL_TO_ACTION_SELECTION ]: 'sign_up',
	};

	function applyOperations( baseAssetGroup, operations ) {
		const nextAssets = structuredClone( baseAssetGroup.assets );

		operations.forEach( ( operation ) => {
			const { field_type: key, id, content } = operation;
			const isMultiple = Array.isArray( filledAssetGroup.assets[ key ] );
			let asset = nextAssets[ key ];

			if ( id === null ) {
				if ( isMultiple ) {
					if ( ! asset ) {
						asset = [];
					}
					asset.push( { content } );
				} else {
					asset = { content };
				}
			} else if ( content === null ) {
				if ( isMultiple ) {
					asset = asset.filter( ( item ) => item.id !== id );
				} else {
					asset = { content: '' };
				}
			}

			nextAssets[ key ] = asset;
		} );

		const appliedValues = {};

		Object.entries( nextAssets ).forEach( ( [ key, asset ] ) => {
			if ( Array.isArray( asset ) ) {
				appliedValues[ key ] = asset.map( ( { content } ) => content );
			} else {
				appliedValues[ key ] = asset.content;
			}
		} );

		return appliedValues;
	}

	let assetGroup;
	let values;

	beforeEach( () => {
		assetGroup = structuredClone( filledAssetGroup );
		values = structuredClone( filledValues );
	} );

	test( 'in each operation, one of `id` and `content` must be null and the other must not be null', () => {
		// If this test failed, the modified code might be problematic or
		// need adjustment for corresponding tests.
		values[ ASSET_FORM_KEY.BUSINESS_NAME ] = 'Better Clothing';
		values[ ASSET_FORM_KEY.DESCRIPTION ] = [ 'Description' ];

		const operations = diffAssetOperations( assetGroup, values );

		expect( operations.length >= 4 ).toBe( true );
		operations.forEach( ( { id, content } ) => {
			// eslint-disable-next-line no-bitwise
			expect( ( id === null ) ^ ( content === null ) ).toBe( 1 );
		} );
	} );

	// Test approach:
	//
	// 1. Simulate an existing asset group and the current values of the assets form.
	// 2. Pass the asset group and form values into the test target function to get
	//    the diff operations.
	// 3. Check whether applying the diff operations to the asset group can result in
	//    the same form values.
	// 4. If yes, then we can infer the target function is correct since the diff operations
	//    can be used to mutate the asset group to the same form values.
	describe( 'should get the same asset contents after applying the diff operations to the asset group', () => {
		test( 'no differences', () => {
			const operations = diffAssetOperations( assetGroup, values );

			expect( operations ).toHaveLength( 0 );
		} );

		test( 'create assets', () => {
			delete assetGroup.assets[ ASSET_KEY.BUSINESS_NAME ];
			delete assetGroup.assets[ ASSET_KEY.CALL_TO_ACTION_SELECTION ];
			values[ ASSET_FORM_KEY.MARKETING_IMAGE ].push(
				'https://example/image-2.png'
			);
			values[ ASSET_FORM_KEY.LOGO ].push( 'https://example/logo-3.png' );
			values[ ASSET_FORM_KEY.LOGO ].push( 'https://example/logo-4.png' );
			values[ ASSET_FORM_KEY.HEADLINE ].push( 'headline 4' );
			values[ ASSET_FORM_KEY.HEADLINE ].push( 'headline 5' );
			values[ ASSET_FORM_KEY.LONG_HEADLINE ].unshift( 'long headline 0' );

			const operations = diffAssetOperations( assetGroup, values );
			const appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );
		} );

		test( 'delete assets', () => {
			values[ ASSET_FORM_KEY.BUSINESS_NAME ] = '';
			values[ ASSET_FORM_KEY.CALL_TO_ACTION_SELECTION ] = '';
			values[ ASSET_FORM_KEY.MARKETING_IMAGE ] = [];
			values[ ASSET_FORM_KEY.SQUARE_MARKETING_IMAGE ] = [];
			values[ ASSET_FORM_KEY.LOGO ].shift();
			values[ ASSET_FORM_KEY.LOGO ].pop();
			values[ ASSET_FORM_KEY.HEADLINE ] = [];
			values[ ASSET_FORM_KEY.DESCRIPTION ] = [];
			values[ ASSET_FORM_KEY.LONG_HEADLINE ] = [];

			const operations = diffAssetOperations( assetGroup, values );
			const appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );
		} );

		test( 'create and also delete assets', () => {
			values[ ASSET_FORM_KEY.BUSINESS_NAME ] = 'Better Clothing';
			values[ ASSET_FORM_KEY.CALL_TO_ACTION_SELECTION ] = 'book_now';
			values[ ASSET_FORM_KEY.PORTRAIT_MARKETING_IMAGE ] = [];
			values[ ASSET_FORM_KEY.DESCRIPTION ].push( 'description 2' );
			values[ ASSET_FORM_KEY.LONG_HEADLINE ].push( 'long headline 2' );

			const operations = diffAssetOperations( assetGroup, values );
			const appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );
		} );

		test( 'create assets with the empty asset group', () => {
			assetGroup.assets = {};

			const operations = diffAssetOperations( assetGroup, values );
			const appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );
		} );

		test( 'reverse the values in the same asset', () => {
			values[ ASSET_FORM_KEY.LOGO ].reverse();
			values[ ASSET_FORM_KEY.HEADLINE ].reverse();

			const operations = diffAssetOperations( assetGroup, values );
			const appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );
		} );

		test( 'swap the values in the same asset', () => {
			const logos = values[ ASSET_FORM_KEY.LOGO ];
			const swapLogo = ( indexA, indexB ) => {
				const tmp = logos[ indexA ];
				logos[ indexA ] = logos[ indexB ];
				logos[ indexB ] = tmp;
			};

			let operations;
			let appliedValues;

			swapLogo( 0, 1 );
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );

			swapLogo( 1, 2 );
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );

			swapLogo( 0, 2 );
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );
		} );

		test( 'update the array value of the assets', () => {
			let operations;
			let appliedValues;

			values[ ASSET_FORM_KEY.LOGO ][ 0 ] = 'https://example/logo-a.jpg';
			values[ ASSET_FORM_KEY.HEADLINE ][ 0 ] = 'Headline A';
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );

			values[ ASSET_FORM_KEY.LOGO ][ 1 ] = 'https://example/logo-b.jpg';
			values[ ASSET_FORM_KEY.HEADLINE ][ 1 ] = 'Headline B';
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );

			values[ ASSET_FORM_KEY.LOGO ][ 2 ] = 'https://example/logo-c.jpg';
			values[ ASSET_FORM_KEY.HEADLINE ][ 2 ] = 'Headline C';
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );

			values[ ASSET_FORM_KEY.HEADLINE ][ 0 ] = 'Headline AA';
			values[ ASSET_FORM_KEY.HEADLINE ][ 1 ] = 'Headline BB';
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );

			values[ ASSET_FORM_KEY.HEADLINE ][ 1 ] = 'Headline BBB';
			values[ ASSET_FORM_KEY.HEADLINE ][ 2 ] = 'Headline CCC';
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );

			values[ ASSET_FORM_KEY.HEADLINE ][ 0 ] = 'Headline AAAA';
			values[ ASSET_FORM_KEY.HEADLINE ][ 2 ] = 'Headline BBBB';
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );

			values[ ASSET_FORM_KEY.HEADLINE ][ 0 ] = 'Headline AAAAA';
			values[ ASSET_FORM_KEY.HEADLINE ][ 1 ] = 'Headline BBBBB';
			values[ ASSET_FORM_KEY.HEADLINE ][ 2 ] = 'Headline CCCCC';
			operations = diffAssetOperations( assetGroup, values );
			appliedValues = applyOperations( assetGroup, operations );

			expect( appliedValues ).toEqual( values );
		} );
	} );
} );
