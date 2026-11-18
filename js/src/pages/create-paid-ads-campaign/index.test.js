/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { setSettings } from '@wordpress/date';
import apiFetch from '@wordpress/api-fetch';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import CreatePaidAdsCampaign from './';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';
import useApplyCYOIncentive from '~/hooks/useApplyCYOIncentive';
import { useAppDispatch } from '~/data';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn(),
} ) );

jest.mock( '~/hooks/useLayout', () => jest.fn() );
jest.mock( '~/hooks/useGoogleMCAccount' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes' );
jest.mock( '~/hooks/useEuPoliticalDeclarationContext' );
jest.mock( '~/hooks/useApplyCYOIncentive' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn(),
} ) );
jest.mock( '~/components/stepper/top-bar', () => () => null );
jest.mock( '~/components/help-icon-button', () => () => null );
jest.mock( '~/components/eu-political-declaration', () => () => null );

// Form values each test can override before submitting.
let mockSubmitValues;

// Replaces the form with a button that submits `mockSubmitValues`, so the test drives `handleSubmit` directly.
jest.mock( '~/components/paid-ads/campaign-assets-form', () =>
	jest.fn( ( props ) => (
		<button
			onClick={ () =>
				props.onSubmit( mockSubmitValues, {
					signalFailedSubmission: jest.fn(),
				} )
			}
		>
			Submit
		</button>
	) )
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

const CAMPAIGN_NOTICE = 'You’ve successfully created a campaign!';
const CAMPAIGN_WITH_OFFER_NOTICE =
	'Campaign created. Your Ads credit offer is applied to your Google Ads account.';

describe( 'CreatePaidAdsCampaign', () => {
	let applyIncentive;
	let createAdsCampaign;
	let createAdsWithAssetsCampaign;
	let updateCampaignAssetGroup;
	let createNotice;
	let push;

	beforeEach( () => {
		jest.clearAllMocks();

		mockSubmitValues = {
			dailyBudget: 50,
			hasConfirmedEuPoliticalContent: false,
			incentiveOffer: 'medium',
			campaignName: 'Summer sale',
			advertisingSource: 'product',
			includeCreativeAssets: false,
			final_url: 'https://example.com/services',
			display_url_path: [ 'book', 'now' ],
		};

		applyIncentive = jest.fn().mockResolvedValue( true );
		createAdsCampaign = jest
			.fn()
			.mockResolvedValue( { createdCampaign: { id: 123 } } );
		createAdsWithAssetsCampaign = jest
			.fn()
			.mockResolvedValue( { createdCampaign: { id: 456 } } );
		updateCampaignAssetGroup = jest.fn().mockResolvedValue( {} );
		createNotice = jest.fn();
		push = jest.fn();

		apiFetch.mockResolvedValue( [
			{
				id: 789,
				final_url: '',
				display_url_path: [ '', '' ],
				assets: {},
			},
		] );

		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			data: [ 'US' ],
		} );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
		useEuPoliticalDeclarationContext.mockReturnValue( {
			handleError: jest.fn(),
		} );
		useApplyCYOIncentive.mockReturnValue( { applyIncentive } );
		useAppDispatch.mockReturnValue( {
			createAdsCampaign,
			createAdsWithAssetsCampaign,
			updateCampaignAssetGroup,
		} );
		getHistory.mockReturnValue( { push } );
	} );

	afterEach( () => {
		jest.useRealTimers();
		setSiteTimezoneOffset( 0 );
	} );

	const submit = async () => {
		render( <CreatePaidAdsCampaign /> );
		await userEvent.click( screen.getByRole( 'button' ) );
		await waitFor( () => expect( push ).toHaveBeenCalled() );
	};

	it( 'applies the selected offer before creating the campaign', async () => {
		await submit();

		expect( applyIncentive ).toHaveBeenCalledWith( 'medium' );
		expect( applyIncentive.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			createAdsCampaign.mock.invocationCallOrder[ 0 ]
		);
	} );

	it( 'mentions the offer in the success notice and tracks it when the offer applies', async () => {
		await submit();

		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			CAMPAIGN_WITH_OFFER_NOTICE
		);
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_campaign_with_cyo_incentive_selected',
			{ context: 'create-ads', level: 'medium' }
		);
	} );

	it( 'still creates the campaign with the usual notice when the offer fails to apply', async () => {
		applyIncentive.mockResolvedValue( false );

		await submit();

		expect( createAdsCampaign ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			CAMPAIGN_NOTICE
		);
		expect( recordGlaEvent ).not.toHaveBeenCalledWith(
			'gla_campaign_with_cyo_incentive_selected',
			expect.anything()
		);
	} );

	it( 'mentions the offer in the success notice when creating the campaign is retried after a failure', async () => {
		// Once an offer is applied, `applyIncentive` returns false on a later attempt.
		applyIncentive.mockResolvedValueOnce( true ).mockResolvedValue( false );
		createAdsCampaign.mockRejectedValueOnce( new Error( 'Failed' ) );

		render( <CreatePaidAdsCampaign /> );
		const submitButton = screen.getByRole( 'button' );

		await userEvent.click( submitButton );
		await waitFor( () =>
			expect( createAdsCampaign ).toHaveBeenCalledTimes( 1 )
		);
		expect( createNotice ).not.toHaveBeenCalled();

		await userEvent.click( submitButton );
		await waitFor( () => expect( push ).toHaveBeenCalled() );

		expect( applyIncentive ).toHaveBeenCalledTimes( 2 );
		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			CAMPAIGN_WITH_OFFER_NOTICE
		);
	} );

	it( 'Should create a product campaign without assets when the merchant did not opt in to assets', async () => {
		await submit();

		expect( createAdsCampaign ).toHaveBeenCalledWith( 50, [ 'US' ], false, {
			name: 'Summer sale',
			advertisingSource: 'product',
		} );
		expect( apiFetch ).not.toHaveBeenCalled();
		expect( updateCampaignAssetGroup ).not.toHaveBeenCalled();
		expect( createAdsWithAssetsCampaign ).not.toHaveBeenCalled();
	} );

	it( 'Should create a product campaign and then update its asset group when the merchant opted in to assets', async () => {
		mockSubmitValues.includeCreativeAssets = true;

		await submit();

		expect( createAdsCampaign ).toHaveBeenCalledWith( 50, [ 'US' ], false, {
			name: 'Summer sale',
			advertisingSource: 'product',
		} );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/wc/gla/ads/campaigns/asset-groups?campaign_id=123',
		} );
		expect( updateCampaignAssetGroup ).toHaveBeenCalledWith(
			789,
			expect.objectContaining( {
				final_url: 'https://example.com/services',
				path1: 'book',
				path2: 'now',
			} )
		);
		expect( createAdsWithAssetsCampaign ).not.toHaveBeenCalled();
	} );

	it.each( [
		[ 'connected', true ],
		[ 'not connected', false ],
	] )(
		'Should create a page campaign with its assets in one request when Merchant Center is %s',
		async ( _, hasGoogleMCConnection ) => {
			useGoogleMCAccount.mockReturnValue( {
				hasFinishedResolution: true,
				hasGoogleMCConnection,
			} );
			mockSubmitValues.advertisingSource = 'page';

			await submit();

			expect( createAdsWithAssetsCampaign ).toHaveBeenCalledWith(
				50,
				[ 'US' ],
				expect.objectContaining( {
					final_url: 'https://example.com/services',
					path1: 'book',
					path2: 'now',
				} ),
				false,
				{ name: 'Summer sale', advertisingSource: 'page' }
			);
			expect( createAdsCampaign ).not.toHaveBeenCalled();
			expect( updateCampaignAssetGroup ).not.toHaveBeenCalled();
		}
	);

	it( 'Should not create a second campaign when updating the asset group is retried after a failure', async () => {
		mockSubmitValues.includeCreativeAssets = true;
		updateCampaignAssetGroup.mockRejectedValueOnce( new Error( 'Failed' ) );

		render( <CreatePaidAdsCampaign /> );
		const submitButton = screen.getByRole( 'button' );

		await userEvent.click( submitButton );
		await waitFor( () =>
			expect( updateCampaignAssetGroup ).toHaveBeenCalledTimes( 1 )
		);
		expect( push ).not.toHaveBeenCalled();

		await userEvent.click( submitButton );
		await waitFor( () => expect( push ).toHaveBeenCalled() );

		expect( createAdsCampaign ).toHaveBeenCalledTimes( 1 );
		expect( updateCampaignAssetGroup ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'Should not create a second campaign when the advertising source is changed to page before retrying', async () => {
		mockSubmitValues.includeCreativeAssets = true;
		updateCampaignAssetGroup.mockRejectedValueOnce( new Error( 'Failed' ) );

		render( <CreatePaidAdsCampaign /> );
		const submitButton = screen.getByRole( 'button' );

		await userEvent.click( submitButton );
		await waitFor( () =>
			expect( updateCampaignAssetGroup ).toHaveBeenCalledTimes( 1 )
		);

		mockSubmitValues.advertisingSource = 'page';

		await userEvent.click( submitButton );
		await waitFor( () => expect( push ).toHaveBeenCalled() );

		expect( createAdsCampaign ).toHaveBeenCalledTimes( 1 );
		expect( createAdsWithAssetsCampaign ).not.toHaveBeenCalled();
		expect( updateCampaignAssetGroup ).toHaveBeenCalledTimes( 2 );
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
