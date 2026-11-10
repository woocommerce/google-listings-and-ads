/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import SetupPaidAds from './setup-paid-ads';
import useAdsSetupCompleteCallback from '~/hooks/useAdsSetupCompleteCallback';
import useApplyCYOIncentive from '~/hooks/useApplyCYOIncentive';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import { recordGlaEvent } from '~/utils/tracks';

// A hash URL keeps jsdom from attempting a real navigation when the redirect sets `window.location.href`.
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getNewPath: jest.fn().mockReturnValue( '#next' ),
} ) );
jest.mock( '~/hooks/useGoogleAdsAccountBillingStatus', () =>
	jest.fn().mockReturnValue( { billingStatus: { status: 'approved' } } )
);
jest.mock( '~/hooks/useAdminUrl', () => jest.fn().mockReturnValue( '' ) );
jest.mock( '~/hooks/useEventPropertiesFilter', () =>
	jest.fn().mockReturnValue( ( props ) => props )
);
jest.mock( '~/hooks/useEuPoliticalDeclarationContext', () =>
	jest.fn().mockReturnValue( { handleError: jest.fn() } )
);
jest.mock( '~/hooks/useAdsSetupCompleteCallback' );
jest.mock( '~/hooks/useApplyCYOIncentive' );
jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes' );
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn(),
} ) );

// Replaces the form with a button that submits the given values, so the test drives `handleSubmit` directly.
jest.mock( '~/components/paid-ads/campaign-assets-form', () => ( props ) => (
	<button
		onClick={ () =>
			props.onSubmit( {
				level: 'recommended',
				dailyBudget: 50,
				hasConfirmedEuPoliticalContent: false,
				incentiveOffer: 'medium',
			} )
		}
	>
		Submit
	</button>
) );

describe( 'SetupPaidAds', () => {
	let applyIncentive;
	let handleSetupComplete;

	beforeEach( () => {
		jest.clearAllMocks();

		applyIncentive = jest.fn().mockResolvedValue( true );
		handleSetupComplete = jest.fn( ( ...args ) => {
			const onCompleted = args[ 3 ];
			onCompleted();
			return Promise.resolve();
		} );

		useApplyCYOIncentive.mockReturnValue( {
			applyIncentive,
			loading: false,
		} );
		useAdsSetupCompleteCallback.mockReturnValue( [
			handleSetupComplete,
			false,
		] );
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			data: [ 'US' ],
		} );
	} );

	const submit = async () => {
		render( <SetupPaidAds /> );
		await userEvent.click( screen.getByRole( 'button' ) );
		await waitFor( () => expect( handleSetupComplete ).toHaveBeenCalled() );
	};

	it( 'applies the offer and adds the query arg to the redirect when it applies', async () => {
		await submit();

		expect( applyIncentive ).toHaveBeenCalledWith( 'medium' );
		expect( applyIncentive.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			handleSetupComplete.mock.invocationCallOrder[ 0 ]
		);
		expect( getNewPath ).toHaveBeenCalledWith(
			{ guide: 'campaign-creation-success', incentive: 'applied' },
			'/google/dashboard'
		);
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_campaign_with_cyo_incentive_selected',
			{ context: 'setup-ads', level: 'medium' }
		);
	} );

	it( 'still creates the campaign without the query arg when the offer fails to apply', async () => {
		applyIncentive.mockResolvedValue( false );

		await submit();

		expect( getNewPath ).toHaveBeenCalledWith(
			{ guide: 'campaign-creation-success' },
			'/google/dashboard'
		);
		expect( recordGlaEvent ).not.toHaveBeenCalledWith(
			'gla_campaign_with_cyo_incentive_selected',
			expect.anything()
		);
	} );
} );
