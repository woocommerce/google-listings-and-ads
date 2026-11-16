/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SetupPaidAds from './setup-paid-ads';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';

jest.mock( '~/components/paid-ads/campaign-assets-form', () =>
	jest.fn( () => null )
);
jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn().mockName( 'useTargetAudienceFinalCountryCodes' )
);
jest.mock( '~/hooks/useGoogleAdsAccountBillingStatus', () =>
	jest.fn().mockName( 'useGoogleAdsAccountBillingStatus' )
);
jest.mock( '~/hooks/useAdsSetupCompleteCallback', () =>
	jest.fn().mockReturnValue( [ jest.fn() ] )
);
jest.mock( '~/hooks/useApplyCYOIncentive', () =>
	jest.fn().mockReturnValue( { applyIncentive: jest.fn(), loading: false } )
);
jest.mock( '~/hooks/useEuPoliticalDeclarationContext', () =>
	jest.fn().mockReturnValue( { handleError: jest.fn() } )
);
jest.mock( '~/hooks/useNavigateAwayPromptEffect', () => jest.fn() );
jest.mock( '~/hooks/useEventPropertiesFilter', () => jest.fn() );
jest.mock( '~/hooks/useAdminUrl', () => jest.fn().mockReturnValue( '' ) );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn().mockReturnValue( {} ),
} ) );
jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn().mockReturnValue( {} ),
} ) );

describe( 'Onboarding SetupPaidAds', () => {
	beforeEach( () => {
		CampaignAssetsForm.mockClear();
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			data: [ 'US' ],
		} );
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: null,
		} );
	} );

	it( 'Should start the campaign with the advertising source product', () => {
		render( <SetupPaidAds /> );

		expect( CampaignAssetsForm.mock.calls.at( -1 )[ 0 ] ).toEqual(
			expect.objectContaining( {
				initialCampaign: expect.objectContaining( {
					advertisingSource: 'product',
				} ),
			} )
		);
	} );
} );
