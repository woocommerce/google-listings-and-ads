/**
 * Internal dependencies
 */
import {
	calcMaxCroppingByFixedRatio,
	getSelectionOptions,
	calcRatioPercentError,
} from './useCroppedImageSelector';

describe( 'calcMaxCroppingByFixedRatio', () => {
	const fn = calcMaxCroppingByFixedRatio;

	it( 'When the width ratio exceeds, the width should be cropped according to the fixed aspect ratio and rounded to an integer.', () => {
		expect( fn( 1000, 1000, 1, 2 ) ).toEqual( [ 500, 1000 ] );
		expect( fn( 1000, 1000, 1, 3 ) ).toEqual( [ 333, 1000 ] );
		expect( fn( 1000, 1000, 2, 3 ) ).toEqual( [ 667, 1000 ] );
	} );

	it( 'When the height ratio exceeds, the height should be cropped according to the fixed aspect ratio and rounded to an integer.', () => {
		expect( fn( 1000, 1000, 2, 1 ) ).toEqual( [ 1000, 500 ] );
		expect( fn( 1000, 1000, 3, 1 ) ).toEqual( [ 1000, 333 ] );
		expect( fn( 1000, 1000, 3, 2 ) ).toEqual( [ 1000, 667 ] );
	} );
} );

describe( 'getSelectionOptions', () => {
	it( 'The returned options should have properties and values that are necessary for use with Media library.', () => {
		const options = getSelectionOptions( 100, 100, 1, 1 );

		expect( options.handles ).toBe( true );
		expect( options.instance ).toBe( true );
		expect( options.persistent ).toBe( true );
	} );

	it( 'The returned options should have imageWidth, imageHeight, minWidth, minHeight, and aspectRatio with the given parameters', () => {
		const options = getSelectionOptions( 1000, 2000, 200, 100 );

		expect( options.imageWidth ).toBe( 1000 );
		expect( options.imageHeight ).toBe( 2000 );
		expect( options.minWidth ).toBe( 200 );
		expect( options.minHeight ).toBe( 100 );
		expect( options.aspectRatio ).toBe( '200:100' );
	} );

	it( 'The coordinates of the selection area in the returned options should be the maximum area according to the aspect ratio and be centered.', () => {
		const landscape = getSelectionOptions( 300, 300, 3, 1 );

		expect( [ landscape.x1, landscape.y1 ] ).toEqual( [ 0, 100 ] );
		expect( [ landscape.x2, landscape.y2 ] ).toEqual( [ 300, 200 ] );

		const portrait = getSelectionOptions( 300, 300, 1, 3 );

		expect( [ portrait.x1, portrait.y1 ] ).toEqual( [ 100, 0 ] );
		expect( [ portrait.x2, portrait.y2 ] ).toEqual( [ 200, 300 ] );

		const square = getSelectionOptions( 300, 300, 1, 1 );

		expect( [ square.x1, square.y1 ] ).toEqual( [ 0, 0 ] );
		expect( [ square.x2, square.y2 ] ).toEqual( [ 300, 300 ] );
	} );
} );

describe( 'calcRatioPercentError', () => {
	it( 'Should calculate the percent error between the actual aspect ratio and the expected aspect ratio.', () => {
		expect( calcRatioPercentError( 100, 100, 1, 1 ) ).toBe( 0 );
		expect( calcRatioPercentError( 101, 100, 1, 1 ) ).toBeCloseTo( 1 );
		expect( calcRatioPercentError( 102, 100, 1, 1 ) ).toBeCloseTo( 2 );
		expect( calcRatioPercentError( 100, 103, 1, 1 ) ).toBeCloseTo( 3 );
		expect( calcRatioPercentError( 100, 104, 1, 1 ) ).toBeCloseTo( 4 );
		expect( calcRatioPercentError( 100, 200, 1, 1 ) ).toBeCloseTo( 100 );
		expect( calcRatioPercentError( 200, 100, 1, 1 ) ).toBeCloseTo( 100 );
	} );
} );
