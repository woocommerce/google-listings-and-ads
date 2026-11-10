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

jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn().mockReturnValue( { data: [ 'US' ] } )
);
jest.mock( '~/hooks/useGoogleAdsAccountBillingStatus', () =>
	jest.fn().mockReturnValue( { billingStatus: { status: 'approved' } } )
);
jest.mock( '~/hooks/useEventPropertiesFilter', () =>
	jest.fn().mockReturnValue( ( props ) => props )
);
jest.mock( '~/hooks/useApplyCYOIncentive' );
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn(),
} ) );
jest.mock( '../clientSession', () => ( {
	getCampaign: jest.fn(),
	setCampaign: jest.fn(),
} ) );
jest.mock( '~/components/paid-ads/budget-incentive-prompt', () =>
	require( '@wordpress/element' ).forwardRef( () => null )
);
jest.mock( '../skip-button', () => ( props ) => (
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

describe( 'SetupPaidAds in ads-only onboarding', () => {
	let applyIncentive;
	let onSubmit;
	let onSkip;

	beforeEach( () => {
		jest.clearAllMocks();

		applyIncentive = jest.fn().mockResolvedValue( true );
		onSubmit = jest.fn();
		onSkip = jest.fn();
		useApplyCYOIncentive.mockReturnValue( {
			applyIncentive,
			loading: false,
		} );
	} );

	it.each( [ true, false ] )(
		'passes whether the offer applied on submit (applied: %s)',
		async ( applied ) => {
			applyIncentive.mockResolvedValue( applied );

			render( <SetupPaidAds onSubmit={ onSubmit } onSkip={ onSkip } /> );
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Submit' } )
			);

			await waitFor( () =>
				expect( onSubmit ).toHaveBeenCalledWith( {
					dailyBudget: 50,
					hasConfirmedEuPoliticalContent: false,
					hasAppliedIncentive: applied,
				} )
			);
		}
	);

	it.each( [ true, false ] )(
		'passes whether the offer applied on skip (applied: %s)',
		async ( applied ) => {
			applyIncentive.mockResolvedValue( applied );

			render( <SetupPaidAds onSubmit={ onSubmit } onSkip={ onSkip } /> );
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Skip' } )
			);

			await waitFor( () =>
				expect( onSkip ).toHaveBeenCalledWith( {
					hasAppliedIncentive: applied,
				} )
			);
		}
	);

	it( 'applies the offer only once when the merchant continues again', async () => {
		render( <SetupPaidAds onSubmit={ onSubmit } onSkip={ onSkip } /> );
		const submitButton = screen.getByRole( 'button', { name: 'Submit' } );

		await userEvent.click( submitButton );
		await waitFor( () => expect( onSubmit ).toHaveBeenCalledTimes( 1 ) );
		await userEvent.click( submitButton );
		await waitFor( () => expect( onSubmit ).toHaveBeenCalledTimes( 2 ) );

		expect( applyIncentive ).toHaveBeenCalledTimes( 1 );
		expect( onSubmit ).toHaveBeenLastCalledWith(
			expect.objectContaining( { hasAppliedIncentive: true } )
		);
	} );
} );
