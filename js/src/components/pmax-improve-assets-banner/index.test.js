/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { getHistory } from '@woocommerce/navigation';
import { useDispatch } from '@wordpress/data';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { getEditCampaignUrl } from '~/utils/urls';
import { PREFERENCES_STORE_NAMESPACE, DAY_IN_SECONDS } from '~/constants';
import PMaxImproveAssetsBanner from './index';
import usePreference from '~/hooks/usePreference';
import useRecommendedPMaxCampaign from '~/hooks/useRecommendedPMaxCampaign';

jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	Spinner: jest.fn( () => <div>spinner</div> ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Notice: ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	),
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	getHistory: jest.fn(),
} ) );

jest.mock( '~/components/app-button', () => ( { children, onClick } ) => (
	<button onClick={ onClick }>{ children }</button>
) );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/hooks/useRecommendedPMaxCampaign', () =>
	jest.fn().mockName( 'useRecommendedPMaxCampaign' )
);

jest.mock( '~/utils/urls', () => ( {
	getEditCampaignUrl: jest.fn( () => '/edit/2/asset-group' ),
} ) );

const recommendedCampaign = { id: 2, name: 'Campaign 2' };

describe( 'PMaxImproveAssetsBanner', () => {
	beforeEach( () => {
		useDispatch.mockReturnValue( { set: () => null } );
	} );

	it( 'renders nothing if expiry is not expired', () => {
		usePreference.mockReturnValue( { expiry: Date.now() + 100000 } );
		useRecommendedPMaxCampaign.mockReturnValue( {
			campaign: recommendedCampaign,
			hasFinishedResolution: true,
		} );
		const { container } = render( <PMaxImproveAssetsBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'resets expiry if expired and renders banner', () => {
		usePreference.mockReturnValue( { expiry: Date.now() - 1000 } );
		useRecommendedPMaxCampaign.mockReturnValue( {
			campaign: recommendedCampaign,
			hasFinishedResolution: true,
		} );
		render( <PMaxImproveAssetsBanner /> );
		expect(
			screen.getByRole( 'button', { name: 'Improve Assets' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		).toBeInTheDocument();
	} );

	it( 'renders nothing if no recommended campaign', () => {
		usePreference.mockReturnValue( {} );
		useRecommendedPMaxCampaign.mockReturnValue( {
			campaign: null,
			hasFinishedResolution: true,
		} );
		const { container } = render( <PMaxImproveAssetsBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders banner for highest-spending enabled PMAX campaign with recommendation', () => {
		usePreference.mockReturnValue( {} );
		useRecommendedPMaxCampaign.mockReturnValue( {
			campaign: recommendedCampaign,
			hasFinishedResolution: true,
		} );
		render( <PMaxImproveAssetsBanner /> );
		expect(
			screen.getByText(
				/Unlock more sales for your campaign, Campaign 2/
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Improve Assets' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		).toBeInTheDocument();
	} );

	it( 'navigates to edit campaign and sets expiry when Improve Assets is clicked', () => {
		const setMock = jest.fn();
		useDispatch.mockReturnValue( { set: setMock } );

		const historyPush = jest.fn().mockName( 'getHistory().push' );
		getHistory.mockReturnValue( { push: historyPush } );
		usePreference.mockReturnValue( {} );
		useRecommendedPMaxCampaign.mockReturnValue( {
			campaign: recommendedCampaign,
			hasFinishedResolution: true,
		} );

		const MOCK_NOW = 1_700_000_000_000;
		jest.spyOn( Date, 'now' ).mockReturnValue( MOCK_NOW );

		render( <PMaxImproveAssetsBanner /> );
		const improveAssetsButton = screen.getByRole( 'button', {
			name: 'Improve Assets',
		} );

		fireEvent.click( improveAssetsButton );

		const expectedExpiry = MOCK_NOW + DAY_IN_SECONDS * 30 * 1000; // 30 days

		expect( setMock ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			'pmax-improve-assets-banner',
			{
				expiry: expectedExpiry,
			}
		);

		expect( setMock ).toHaveBeenCalled();
		expect( getEditCampaignUrl ).toHaveBeenCalledWith( 2, 'asset-group' );
		expect( historyPush ).toHaveBeenCalledWith( '/edit/2/asset-group' );
	} );
} );
