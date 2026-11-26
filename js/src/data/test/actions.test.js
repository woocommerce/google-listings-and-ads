/**
 * Internal dependencies
 */
import {
	receiveGenAIMediaAssets,
	receiveGenAITextAssets,
	replaceGenAIMediaAsset,
} from '../actions';
import TYPES from '../action-types';

describe( 'Gen AI asset actions', () => {
	describe( 'receiveGenAIMediaAssets', () => {
		it( 'adapts API items into a grouped-by-type data payload', () => {
			const generator = receiveGenAIMediaAssets(
				'https://example.com',
				{
					items: [
						{
							type: 'marketing_image',
							temporary_image_url: 'https://example.com/1.png',
						},
					],
				},
				'marketing_image'
			);

			const { value } = generator.next();

			expect( value ).toEqual( {
				type: TYPES.RECEIVE_GEN_AI_MEDIA_ASSETS,
				url: 'https://example.com',
				assetType: 'marketing_image',
				data: {
					marketing_image: [ 'https://example.com/1.png' ],
				},
			} );
		} );

		it( 'returns an empty data payload when there are no items', () => {
			const generator = receiveGenAIMediaAssets(
				'https://example.com',
				{},
				'marketing_image'
			);

			const { value } = generator.next();

			expect( value ).toEqual( {
				type: TYPES.RECEIVE_GEN_AI_MEDIA_ASSETS,
				url: 'https://example.com',
				assetType: 'marketing_image',
				data: {},
			} );
		} );
	} );

	describe( 'receiveGenAITextAssets', () => {
		it( 'adapts API items into a grouped-by-type data payload', () => {
			const generator = receiveGenAITextAssets(
				'https://example.com',
				{
					items: [ { type: 'headline', text: 'Great deals' } ],
				},
				'headline'
			);

			const { value } = generator.next();

			expect( value ).toEqual( {
				type: TYPES.RECEIVE_GEN_AI_TEXT_ASSETS,
				url: 'https://example.com',
				assetType: 'headline',
				data: { headline: [ 'Great deals' ] },
			} );
		} );

		it( 'returns an empty data payload when there are no items', () => {
			const generator = receiveGenAITextAssets(
				'https://example.com',
				{},
				'headline'
			);

			const { value } = generator.next();

			expect( value ).toEqual( {
				type: TYPES.RECEIVE_GEN_AI_TEXT_ASSETS,
				url: 'https://example.com',
				assetType: 'headline',
				data: {},
			} );
		} );
	} );

	describe( 'replaceGenAIMediaAsset', () => {
		it( 'returns a REPLACE_GEN_AI_MEDIA_ASSET action carrying the source and new URLs', () => {
			const generator = replaceGenAIMediaAsset(
				'https://example.com',
				'marketing_image',
				'https://example.com/source.png',
				'https://example.com/new.png'
			);

			const { value } = generator.next();

			expect( value ).toEqual( {
				type: TYPES.REPLACE_GEN_AI_MEDIA_ASSET,
				url: 'https://example.com',
				assetType: 'marketing_image',
				sourceUrl: 'https://example.com/source.png',
				newUrl: 'https://example.com/new.png',
			} );
		} );
	} );
} );
