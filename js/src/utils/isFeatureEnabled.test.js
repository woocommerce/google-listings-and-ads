/**
 * Internal dependencies
 */
import { isFeatureEnabled } from './isFeatureEnabled';

jest.mock( '~/constants', () => {
	return {
		glaData: {
			enabledFeatures: [ 'featureB' ],
		},
	};
} );

describe( 'isFeatureEnabled', () => {
	it( 'returns true if the feature is enabled', () => {
		const features = new Set( [ 'featureA', 'featureB' ] );
		expect( isFeatureEnabled( 'featureA', features ) ).toBe( true );
		expect( isFeatureEnabled( 'featureB', features ) ).toBe( true );
	} );

	it( 'returns false if the feature is not enabled', () => {
		const features = new Set( [ 'featureA', 'featureB' ] );
		expect( isFeatureEnabled( 'featureC', features ) ).toBe( false );
	} );

	it( 'returns false if _enabledFeatures is not a Set', () => {
		expect( isFeatureEnabled( 'featureA', [ 'featureA' ] ) ).toBe( false );
		expect( isFeatureEnabled( 'featureA', null ) ).toBe( false );
		expect( isFeatureEnabled( 'featureA', undefined ) ).toBe( false );
		expect( isFeatureEnabled( 'featureA', {} ) ).toBe( false );
	} );

	it( 'returns false if feature is not provided', () => {
		const features = new Set( [ 'featureA' ] );
		expect( isFeatureEnabled( undefined, features ) ).toBe( false );
		expect( isFeatureEnabled( '', features ) ).toBe( false );
	} );

	it( 'uses default enabledFeatures when _enabledFeatures is not provided', () => {
		expect( isFeatureEnabled( 'featureB' ) ).toBe( true );
		expect( isFeatureEnabled( 'featureA' ) ).toBe( false );
	} );
} );
