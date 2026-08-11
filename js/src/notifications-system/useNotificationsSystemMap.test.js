/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useNotificationsSystemMap from './useNotificationsSystemMap';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

describe( 'useNotificationsSystemMap', () => {
	beforeEach( () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: true,
			hasFinishedResolution: true,
		} );
	} );

	it( 'defines the collect-google-customer-reviews notification content and CTA', () => {
		const { result } = renderHook( () => useNotificationsSystemMap() );
		const config = result.current[ 'collect-google-customer-reviews' ];

		expect( config.title ).toBe( 'Collect Google Reviews after purchase' );
		expect( config.description ).toBe(
			'Google Reviews provide free social proof, increased organic visibility, and a boost to advertising performance.'
		);
		expect( config.actions ).toHaveLength( 1 );
		expect( config.actions[ 0 ] ).toEqual(
			expect.objectContaining( {
				settingKey: 'collect_reviews_after_purchase',
				children: 'Enable reviews collection',
			} )
		);
	} );

	it( 'defines the google-customer-reviews-badge-widget notification content and CTA', () => {
		const { result } = renderHook( () => useNotificationsSystemMap() );
		const config = result.current[ 'google-customer-reviews-badge-widget' ];

		expect( config.title ).toBe( 'Add your store rating widget' );
		expect( config.description ).toBe(
			'Show Google-verified ratings and reviews on your site and boost shopper trust and conversions.'
		);
		expect( config.actions ).toHaveLength( 1 );
		expect( config.actions[ 0 ] ).toEqual(
			expect.objectContaining( {
				settingKey: 'badge_widget_enabled',
				children: 'Add widget',
			} )
		);
	} );
} );
