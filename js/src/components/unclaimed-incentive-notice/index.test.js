/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { useDispatch } from '@wordpress/data';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import UnclaimedIncentiveNotice from './index';
import usePreference from '~/hooks/usePreference';
import useAdsSettings from '~/hooks/useAdsSettings';

jest.mock( '@wordpress/components', () => ( {
	Notice: ( { children, onRemove } ) => (
		<div>
			{ children }
			<button onClick={ onRemove }>Dismiss</button>
		</div>
	),
} ) );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/hooks/useAdsSettings', () =>
	jest.fn().mockName( 'useAdsSettings' )
);

const NOTICE_DISMISSED_KEY = 'unclaimed-incentive-notice-dismissed';

describe( 'UnclaimedIncentiveNotice', () => {
	beforeEach( () => {
		useDispatch.mockReturnValue( { set: jest.fn() } );
	} );

	it( 'renders nothing when adsSettings is not yet loaded', () => {
		useAdsSettings.mockReturnValue( { adsSettings: null } );
		usePreference.mockReturnValue( false );

		const { container } = render( <UnclaimedIncentiveNotice /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing when has_unclaimed_incentive is false', () => {
		useAdsSettings.mockReturnValue( {
			adsSettings: { has_unclaimed_incentive: false },
		} );
		usePreference.mockReturnValue( false );

		const { container } = render( <UnclaimedIncentiveNotice /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing when the notice has been dismissed', () => {
		useAdsSettings.mockReturnValue( {
			adsSettings: { has_unclaimed_incentive: true },
		} );
		usePreference.mockReturnValue( true );

		const { container } = render( <UnclaimedIncentiveNotice /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders the notice when has_unclaimed_incentive is true and not dismissed', () => {
		useAdsSettings.mockReturnValue( {
			adsSettings: { has_unclaimed_incentive: true },
		} );
		usePreference.mockReturnValue( false );

		render( <UnclaimedIncentiveNotice /> );

		expect(
			screen.getByText(
				'You have an unclaimed incentive available for your Google Ads account.'
			)
		).toBeInTheDocument();
	} );

	it( 'calls set with the dismissed key when the notice is dismissed', () => {
		const setMock = jest.fn();
		useDispatch.mockReturnValue( { set: setMock } );
		useAdsSettings.mockReturnValue( {
			adsSettings: { has_unclaimed_incentive: true },
		} );
		usePreference.mockReturnValue( false );

		render( <UnclaimedIncentiveNotice /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( setMock ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			NOTICE_DISMISSED_KEY,
			true
		);
	} );
} );
