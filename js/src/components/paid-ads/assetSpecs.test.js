/**
 * Internal dependencies
 */
import { ASSET_IMAGE_SPECS } from './assetSpecs';

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
