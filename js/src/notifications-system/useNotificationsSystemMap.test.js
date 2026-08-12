/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useNotificationsSystemMap from './useNotificationsSystemMap';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useSettings from '~/hooks/useSettings';
import { handleApiError } from '~/utils/handleError';

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

jest.mock( '~/hooks/useSettings', () => jest.fn().mockName( 'useSettings' ) );

jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

const saveSettings = jest.fn();

const createMockEvent = () => ( { preventDefault: jest.fn() } );

describe( 'useNotificationsSystemMap', () => {
	const originalLocation = window.location;
	let locationAssignSpy;

	afterAll( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: originalLocation,
		} );
	} );

	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: true,
			hasFinishedResolution: true,
		} );
		useSettings.mockReturnValue( {
			settings: {
				collect_reviews_after_purchase: false,
				badge_widget_enabled: false,
			},
			saveSettings,
		} );

		locationAssignSpy = jest.fn();
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: { ...originalLocation, assign: locationAssignSpy },
		} );
	} );

	it( 'defines the google-customer-reviews-collect-reviews notification content and CTA', () => {
		const { result } = renderHook( () => useNotificationsSystemMap() );
		const config =
			result.current[ 'google-customer-reviews-collect-reviews' ];

		expect( config.title ).toBe( 'Collect Google Reviews after purchase' );
		expect( config.description ).toBe(
			'Google Reviews provide free social proof, increased organic visibility, and a boost to advertising performance.'
		);
		expect( config.actions ).toHaveLength( 1 );
		expect( config.actions[ 0 ] ).toEqual(
			expect.objectContaining( {
				children: 'Enable reviews collection',
				onClick: expect.any( Function ),
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
				children: 'Add widget',
				onClick: expect.any( Function ),
			} )
		);
	} );

	it( "saves collect_reviews_after_purchase and navigates when the collect-reviews action's onClick fires", async () => {
		saveSettings.mockResolvedValue( {} );

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ];
		const event = createMockEvent();

		await act( async () => {
			await action.onClick( event, action );
		} );

		expect( event.preventDefault ).toHaveBeenCalledTimes( 1 );
		expect( saveSettings ).toHaveBeenCalledWith(
			expect.objectContaining( {
				collect_reviews_after_purchase: true,
			} )
		);
		expect( locationAssignSpy ).toHaveBeenCalledWith(
			expect.stringContaining( action.href )
		);
	} );

	it( "saves badge_widget_enabled and navigates when the badge-widget action's onClick fires", async () => {
		saveSettings.mockResolvedValue( {} );

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-badge-widget' ]
				.actions[ 0 ];
		const event = createMockEvent();

		await act( async () => {
			await action.onClick( event, action );
		} );

		expect( event.preventDefault ).toHaveBeenCalledTimes( 1 );
		expect( saveSettings ).toHaveBeenCalledWith(
			expect.objectContaining( {
				badge_widget_enabled: true,
			} )
		);
		expect( locationAssignSpy ).toHaveBeenCalledWith(
			expect.stringContaining( action.href )
		);
	} );

	it( 'does not navigate and reports an error when saving the setting fails', async () => {
		const error = { message: 'Something went wrong' };
		saveSettings.mockRejectedValue( error );

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ];

		await act( async () => {
			await action.onClick( createMockEvent(), action );
		} );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			expect.any( String )
		);
		expect( locationAssignSpy ).not.toHaveBeenCalled();
	} );

	it( "disables the collect-reviews action's own button while its setting is saving", async () => {
		let resolveSave;
		saveSettings.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveSave = resolve;
			} )
		);

		const { result } = renderHook( () => useNotificationsSystemMap() );
		const action =
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ];

		act( () => {
			action.onClick( createMockEvent(), action );
		} );

		expect(
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ].disabled
		).toBe( true );
		expect(
			result.current[ 'google-customer-reviews-badge-widget' ]
				.actions[ 0 ].disabled
		).toBe( false );

		await act( async () => {
			resolveSave( {} );
		} );

		expect(
			result.current[ 'google-customer-reviews-collect-reviews' ]
				.actions[ 0 ].disabled
		).toBe( false );
	} );
} );
