/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';
import { setSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import CreatePaidAdsCampaign from './index';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';

jest.mock( '~/hooks/useLayout', () => jest.fn() );

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn().mockName( 'useTargetAudienceFinalCountryCodes' )
);

jest.mock( '~/hooks/useEuPoliticalDeclarationContext', () =>
	jest.fn().mockReturnValue( { handleError: jest.fn() } )
);

jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn().mockReturnValue( {} ),
} ) );

jest.mock( '~/components/eu-political-declaration', () =>
	jest.fn( () => null )
);
jest.mock( '~/components/stepper/top-bar', () => jest.fn( () => null ) );
jest.mock( '~/components/help-icon-button', () => jest.fn( () => null ) );
jest.mock( '~/components/paid-ads/ads-campaign', () => jest.fn( () => null ) );
jest.mock( '~/components/paid-ads/asset-group', () => ( {
	__esModule: true,
	default: jest.fn( () => null ),
	ACTION_SUBMIT_CAMPAIGN_AND_ASSETS: 'submit-campaign-and-assets',
} ) );
jest.mock( '~/components/paid-ads/campaign-assets-form', () =>
	jest.fn( () => null )
);

function mockCurrentTime( isoDateTime ) {
	jest.useFakeTimers( {
		now: new Date( isoDateTime ),
		doNotFake: [
			'setTimeout',
			'clearTimeout',
			'setInterval',
			'clearInterval',
			'setImmediate',
			'clearImmediate',
			'nextTick',
			'queueMicrotask',
			'requestAnimationFrame',
			'cancelAnimationFrame',
			'requestIdleCallback',
			'cancelIdleCallback',
			'performance',
		],
	} );
}

function setSiteTimezoneOffset( offset ) {
	setSettings( {
		l10n: { locale: 'en_US' },
		formats: { datetime: 'Y-m-d H:i:s' },
		timezone: { offset, string: '' },
	} );
}

function getLastInitialCampaign() {
	return CampaignAssetsForm.mock.calls.at( -1 )[ 0 ].initialCampaign;
}

describe( 'CreatePaidAdsCampaign', () => {
	beforeEach( () => {
		CampaignAssetsForm.mockClear();

		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			data: [ 'US' ],
		} );
		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );
	} );

	afterEach( () => {
		jest.useRealTimers();
		setSiteTimezoneOffset( 0 );
	} );

	it( 'Should start the advertising source as product when Merchant Center is connected', () => {
		render( <CreatePaidAdsCampaign /> );

		expect( getLastInitialCampaign() ).toEqual(
			expect.objectContaining( { advertisingSource: 'product' } )
		);
	} );

	it( 'Should start the advertising source as page when Merchant Center is not connected', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: false,
		} );

		render( <CreatePaidAdsCampaign /> );

		expect( getLastInitialCampaign() ).toEqual(
			expect.objectContaining( { advertisingSource: 'page' } )
		);
	} );

	it( 'Should wait for the Merchant Center account to resolve before rendering the form', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: false,
			hasGoogleMCConnection: false,
		} );

		const { rerender } = render( <CreatePaidAdsCampaign /> );

		expect( CampaignAssetsForm ).not.toHaveBeenCalled();

		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );

		rerender( <CreatePaidAdsCampaign /> );

		expect( CampaignAssetsForm ).toHaveBeenCalled();
		CampaignAssetsForm.mock.calls.forEach( ( [ props ] ) => {
			expect( props.initialCampaign.advertisingSource ).toBe( 'product' );
		} );
	} );

	it( 'Should start a new campaign with a generated name in the site timezone', () => {
		mockCurrentTime( '2026-11-05T20:30:00Z' );
		setSiteTimezoneOffset( 9 );

		render( <CreatePaidAdsCampaign /> );

		expect( getLastInitialCampaign() ).toEqual(
			expect.objectContaining( {
				campaignName: 'Campaign 2026-11-06 05:30:00',
			} )
		);
	} );
} );
