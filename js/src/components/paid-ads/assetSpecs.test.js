/**
 * Internal dependencies
 */
import { ASSET_IMAGE_SPECS, ASSET_TEXT_SPECS } from './assetSpecs';

describe( 'ASSET_IMAGE_SPECS', () => {
	describe( 'getMax', () => {
		const specs = ASSET_IMAGE_SPECS;
		const keys = specs.map( ( spec ) => spec.key );

		let values;

		function setImagesValues( ...numbersOfImages ) {
			values = {};
			keys.forEach( ( key, i ) => {
				const num = numbersOfImages[ i ] ?? 0;
				values[ key ] = Array.from( { length: num } );
			} );
		}

		function getMaxNumbers() {
			return specs.map( ( spec ) => spec.getMax( values ) );
		}

		it( 'When other numbers of asset images in form values are ≤ spec.min, should consider other spec.min as occupied numbers', () => {
			setImagesValues( 0, 0, 0, 0 );

			expect( getMaxNumbers() ).toEqual( [ 19, 19, 18, 5 ] );

			setImagesValues( 1, 1, 0, 1 );

			expect( getMaxNumbers() ).toEqual( [ 19, 19, 18, 5 ] );
		} );

		it( 'When other numbers of asset images in form values are > spec.min, should consider them as occupied numbers', () => {
			setImagesValues( 2, 1, 0, 2 );

			expect( getMaxNumbers() ).toEqual( [ 19, 18, 17, 5 ] );

			setImagesValues( 1, 10, 0, 5 );

			expect( getMaxNumbers() ).toEqual( [ 10, 19, 9, 5 ] );

			setImagesValues( 5, 15, 0 );

			expect( getMaxNumbers() ).toEqual( [ 5, 15, 0, 5 ] );

			setImagesValues( 1, 1, 1 );

			expect( getMaxNumbers() ).toEqual( [ 18, 18, 18, 5 ] );

			setImagesValues( 9, 1, 10 );

			expect( getMaxNumbers() ).toEqual( [ 9, 1, 10, 5 ] );

			setImagesValues( 5, 5, 5 );

			expect( getMaxNumbers() ).toEqual( [ 10, 10, 10, 5 ] );

			setImagesValues( 2, 4, 6 );

			expect( getMaxNumbers() ).toEqual( [ 10, 12, 14, 5 ] );
		} );
	} );
} );

describe( 'ASSET_TEXT_SPECS', () => {
	const specs = ASSET_TEXT_SPECS;

	it.each(
		specs
			.filter( ( spec ) => {
				return (
					spec.min >= 2 && Array.isArray( spec.maxCharacterCounts )
				);
			} )
			.map( ( spec ) => [ spec.key, spec.maxCharacterCounts ] )
	)(
		'When the text-type asset "%s" requires at least two texts and has multiple maximum character counts, it should have only one smaller count and sort by number in ascending',
		( key, maxCharacterCounts ) => {
			// This test is to ensure the `spec.maxCharacterCounts` follows the specific form
			// because the relevant adapter and validation rely on the specific form described
			// in this test title. For example: `[ 15, 30, 30, 30 ]`.
			//
			// If this test fails, it may be:
			// - there are `maxCharacterCounts` that mismatch the specific form,
			// - a text asset has a single maximum character for its texts, please set its
			//   `maxCharacterCounts` to a number instead of an array of numbers,
			// - or the specific form has been changed, please adjust this test and also
			//   check if the logic relied on `maxCharacterCounts` needs to be adjusted together.
			//
			// Ref: https://developers.google.com/google-ads/api/docs/performance-max/assets#ensure_minimum_asset_requirements_are_met
			const counts = maxCharacterCounts.slice().sort( ( a, b ) => a - b );

			expect( counts ).toEqual( maxCharacterCounts );
			expect( counts.length > 1 ).toBe( true );
			expect( counts[ 0 ] < counts[ 1 ] ).toBe( true );
			expect( new Set( counts ).size ).toBe( 2 );
		}
	);
} );
