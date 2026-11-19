/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import CreatePaidAdsCampaign from './';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';
import useApplyCYOIncentive from '~/hooks/useApplyCYOIncentive';
import { useAppDispatch } from '~/data';
import { recordGlaEvent } from '~/utils/tracks';

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

// Replaces the form with a button that submits the given values, so the test drives `handleSubmit` directly.
jest.mock( '~/components/paid-ads/campaign-assets-form', () => ( props ) => (
	<button
		onClick={ () =>
			props.onSubmit(
				{
					dailyBudget: 50,
					hasConfirmedEuPoliticalContent: false,
					incentiveOffer: 'medium',
				},
				{
					submitter: { dataset: {} },
					signalFailedSubmission: jest.fn(),
				}
			)
		}
	>
		Submit
	</button>
) );

const CAMPAIGN_NOTICE = 'You’ve successfully created a campaign!';
const CAMPAIGN_WITH_OFFER_NOTICE =
	'Campaign created. Your Ads credit offer is applied to your Google Ads account.';

describe( 'CreatePaidAdsCampaign', () => {
	let applyIncentive;
	let createAdsCampaign;
	let createNotice;
	let push;

	beforeEach( () => {
		jest.clearAllMocks();

		applyIncentive = jest.fn().mockResolvedValue( true );
		createAdsCampaign = jest
			.fn()
			.mockResolvedValue( { createdCampaign: { id: 123 } } );
		createNotice = jest.fn();
		push = jest.fn();

		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
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
			createAdsWithAssetsCampaign: jest.fn(),
			updateCampaignAssetGroup: jest.fn(),
		} );
		getHistory.mockReturnValue( { push } );
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
} );
