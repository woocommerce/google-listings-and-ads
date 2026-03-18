/**
 * Internal dependencies
 */
import fillEmptyAssetSlots from './fill-empty-asset-slots';

describe( 'fillEmptyAssetSlots', () => {
	it( 'fills empty slots with generated values preserving non-empty values', () => {
		const current = [ 'a', '', 'b', '' ];
		const generated = [ 'x', 'y' ];
		const result = fillEmptyAssetSlots( current, generated );
		expect( result.assets ).toEqual( [ 'a', 'x', 'b', 'y' ] );
		expect( result.updatedCount ).toBe( 2 );
	} );

	it( 'skips generated values that duplicate existing assets and duplicates in generated list', () => {
		const current = [ 'a', '', '', 'b' ];
		const generated = [ 'a', 'c', 'c', 'd' ];
		const result = fillEmptyAssetSlots( current, generated );
		expect( result.assets ).toEqual( [ 'a', 'c', 'd', 'b' ] );
		expect( result.updatedCount ).toBe( 2 );
	} );

	it( 'leaves slots empty when not enough unique generated assets', () => {
		const current = [ '', '', '' ];
		const generated = [ 'a' ];
		const result = fillEmptyAssetSlots( current, generated );
		expect( result.assets ).toEqual( [ 'a', '', '' ] );
		expect( result.updatedCount ).toBe( 1 );
	} );

	it( 'returns unchanged when there are no empty slots', () => {
		const current = [ 'a', 'b' ];
		const generated = [ 'x', 'y' ];
		const result = fillEmptyAssetSlots( current, generated );
		expect( result.assets ).toEqual( [ 'a', 'b' ] );
		expect( result.updatedCount ).toBe( 0 );
	} );

	it( 'does not reuse the same generated value when it appears multiple times in generatedAssets', () => {
		const current = [ '', '' ];
		const generated = [ 'x', 'x' ];
		const result = fillEmptyAssetSlots( current, generated );
		expect( result.assets ).toEqual( [ 'x', '' ] );
		expect( result.updatedCount ).toBe( 1 );
	} );
} );
