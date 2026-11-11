/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import SetupPaidAds from './setup-paid-ads';
import useApplyCYOIncentive from '~/hooks/useApplyCYOIncentive';
import { getProductFeedUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';

// A hash URL keeps jsdom from attempting a real navigation when the redirect sets `window.location.href`.
jest.mock( '~/utils/urls', () => ( {
	...jest.requireActual( '~/utils/urls' ),
	getProductFeedUrl: jest.fn().mockReturnValue( '#next' ),
} ) );
jest.mock( '~/hooks/useAdminUrl', () => jest.fn().mockReturnValue( '' ) );
jest.mock( '~/hooks/useAdsSetupCompleteCallback', () =>
	jest.fn().mockReturnValue( [ jest.fn().mockResolvedValue() ] )
);
jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn().mockReturnValue( { data: [ 'US' ] } )
);
jest.mock( '~/hooks/useGoogleAdsAccountBillingStatus', () =>
	jest.fn().mockReturnValue( { billingStatus: { status: 'approved' } } )
);
jest.mock( '~/hooks/useEventPropertiesFilter', () =>
	jest.fn().mockReturnValue( ( props ) => props )
);
jest.mock( '~/hooks/useEuPoliticalDeclarationContext', () =>
	jest.fn().mockReturnValue( { handleError: jest.fn() } )
);
jest.mock( '~/hooks/useApplyCYOIncentive' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest
		.fn()
		.mockReturnValue( { syncSettings: jest.fn().mockResolvedValue() } ),
} ) );
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn(),
} ) );
jest.mock( './clientSession', () => ( {
	getCampaign: jest.fn(),
	setCampaign: jest.fn(),
} ) );
jest.mock( '~/components/paid-ads/budget-incentive-prompt', () =>
	require( '@wordpress/element' ).forwardRef( () => null )
);
jest.mock( './skip-button', () => ( props ) => (
	<button onClick={ props.onSkipCreatePaidAds }>Skip</button>
) );
// Renders only the skip button, with the form values it would receive.
jest.mock(
	'~/components/paid-ads/ads-campaign',
	() => ( props ) =>
		props.skipButton( {
			isValidForm: true,
			values: { incentiveOffer: 'medium' },
		} )
);
// Replaces the form with its children and a button that submits the given values.
jest.mock( '~/components/paid-ads/campaign-assets-form', () => ( props ) => (
	<>
		{ props.children }
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
	</>
) );

describe( 'SetupPaidAds in onboarding', () => {
	let applyIncentive;

	beforeEach( () => {
		jest.clearAllMocks();

		applyIncentive = jest.fn().mockResolvedValue( true );
		useApplyCYOIncentive.mockReturnValue( {
			applyIncentive,
			loading: false,
		} );
	} );

	const clickAndWaitForRedirect = async ( name ) => {
		render( <SetupPaidAds /> );
		await userEvent.click( screen.getByRole( 'button', { name } ) );
		await waitFor( () => expect( getProductFeedUrl ).toHaveBeenCalled() );
	};

	it.each( [ 'Submit', 'Skip' ] )(
		'adds the query arg to the redirect when the offer applies (%s)',
		async ( name ) => {
			await clickAndWaitForRedirect( name );

			expect( applyIncentive ).toHaveBeenCalledWith( 'medium' );
			expect( getProductFeedUrl ).toHaveBeenCalledWith( {
				guide: 'submission-success',
				incentive: 'applied',
			} );
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_onboarding_with_cyo_incentive_selected',
				expect.objectContaining( { level: 'medium' } )
			);
		}
	);

	it.each( [ 'Submit', 'Skip' ] )(
		'leaves out the query arg when the offer fails to apply (%s)',
		async ( name ) => {
			applyIncentive.mockResolvedValue( false );

			await clickAndWaitForRedirect( name );

			expect( getProductFeedUrl ).toHaveBeenCalledWith( {
				guide: 'submission-success',
			} );
			expect( recordGlaEvent ).not.toHaveBeenCalledWith(
				'gla_onboarding_with_cyo_incentive_selected',
				expect.anything()
			);
		}
	);

	it( 'uses the result of each submit for the redirect', async () => {
		// Once an offer is applied, `applyIncentive` returns false on a later attempt.
		applyIncentive.mockResolvedValueOnce( true ).mockResolvedValue( false );

		render( <SetupPaidAds /> );
		const submitButton = screen.getByRole( 'button', { name: 'Submit' } );

		await userEvent.click( submitButton );
		await waitFor( () =>
			expect( getProductFeedUrl ).toHaveBeenCalledTimes( 1 )
		);
		await userEvent.click( submitButton );
		await waitFor( () =>
			expect( getProductFeedUrl ).toHaveBeenCalledTimes( 2 )
		);

		expect( getProductFeedUrl ).toHaveBeenNthCalledWith( 1, {
			guide: 'submission-success',
			incentive: 'applied',
		} );
		expect( getProductFeedUrl ).toHaveBeenNthCalledWith( 2, {
			guide: 'submission-success',
		} );
	} );
} );
