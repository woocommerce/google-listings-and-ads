/**
 * Internal dependencies
 */
import { adaptAssetGroup } from './adapters';
import { ASSET_KEY } from '.~/constants';

describe( 'adaptAssetGroup', () => {
	describe( 'Adapts the order of the multi-value text assets', () => {
		const { HEADLINE, DESCRIPTION } = ASSET_KEY;
		const text10Count = '1234567890';
		const text15Count = '123456789012345';
		const text20Count = text10Count.repeat( 2 );
		const text30Count = text10Count.repeat( 3 );
		const text60Count = text10Count.repeat( 6 );
		const text90Count = text10Count.repeat( 9 );

		const mapContent = ( { content } ) => content;

		let assetGroup;

		beforeEach( () => {
			assetGroup = {
				assets: {
					[ HEADLINE ]: [
						{ content: text15Count },
						{ content: text20Count },
						{ content: text30Count },
					],
					[ DESCRIPTION ]: [
						{ content: text60Count },
						{ content: text90Count },
					],
				},
			};
		} );

		it( 'When the target assets do not exist, it should return the same asset group', () => {
			assetGroup.assets = {};

			expect( adaptAssetGroup( assetGroup ) ).toEqual( assetGroup );
		} );

		it( 'When the first text has a valid character count, it should not change the order', () => {
			expect( adaptAssetGroup( assetGroup ) ).toEqual( assetGroup );
		} );

		it( 'When the first text has an invalid character count but there is no other valid one, it should not change the order', () => {
			assetGroup.assets[ DESCRIPTION ].pop();
			assetGroup.assets[ HEADLINE ] = [
				{ content: text30Count },
				{ content: text20Count },
			];

			expect( adaptAssetGroup( assetGroup ) ).toEqual( assetGroup );
		} );

		it( 'When the first text has an invalid character count, it should move the valid one to the first', () => {
			assetGroup.assets[ DESCRIPTION ].reverse();
			assetGroup.assets[ HEADLINE ] = [
				{ content: text20Count },
				{ content: text30Count },
				{ content: text15Count },
				{ content: text10Count },
			];
			const { assets } = adaptAssetGroup( assetGroup );
			const descriptions = assets[ DESCRIPTION ].map( mapContent );
			const headlines = assets[ HEADLINE ].map( mapContent );

			expect( descriptions ).toEqual( [ text60Count, text90Count ] );
			expect( headlines ).toEqual( [
				text15Count,
				text20Count,
				text30Count,
				text10Count,
			] );
		} );
	} );
} );
