jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn().mockName( 'recordEvent' ),
} ) );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn().mockReturnValue( {
		saveTargetAudience: jest.fn(),
		createAdsWithAssetsCampaign: jest.fn(),
		completeOnboarding: jest.fn(),
	} ),
} ) );
jest.mock( '~/hooks/useAdminUrl', () => jest.fn().mockReturnValue( '' ) );
jest.mock( '~/hooks/useCompleteAdsSetup', () =>
	jest.fn().mockReturnValue( { completeAdsSetup: jest.fn() } )
);
jest.mock( '~/hooks/useEventPropertiesFilter', () => jest.fn() );
jest.mock( '~/hooks/useGoogleAdsAccount', () => jest.fn() );
jest.mock( '../useTargetAudienceWithSuggestions', () =>
	jest.fn().mockReturnValue( {} )
);
jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn().mockReturnValue( {} )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockReturnValue( { createNotice: jest.fn() } )
);
// A hash URL keeps jsdom from attempting a real navigation when the redirect sets `window.location.href`.
jest.mock( '~/utils/urls', () => ( {
	getDashboardUrl: jest.fn().mockReturnValue( '#dashboard' ),
} ) );
jest.mock(
	'~/components/eu-political-declaration/eu-political-declaration-provider',
	() =>
		( { children } ) =>
			children
);
jest.mock(
	'~/components/paid-ads/campaign-assets-form',
	() =>
		( { children } ) =>
			children
);
jest.mock( '~/components/paid-ads/asset-group', () =>
	jest.fn().mockName( 'AssetGroup' )
);
jest.mock( './setup-accounts', () => jest.fn().mockName( 'SetupAccounts' ) );
jest.mock( './setup-paid-ads', () => jest.fn().mockName( 'SetupPaidAds' ) );

/**
 * External dependencies
 */
import { render, act, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SavedAdsOnlySetupStepper from '.';
import SetupAccounts from './setup-accounts';
import SetupPaidAds from './setup-paid-ads';
import AssetGroup from '~/components/paid-ads/asset-group';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import { getDashboardUrl } from '~/utils/urls';
import { GUIDE_NAMES, INCENTIVE_APPLIED_QUERY } from '~/constants';

describe( 'SavedAdsOnlySetupStepper', () => {
	let submitSetupPaidAds;
	let skipAssetGroup;

	beforeEach( () => {
		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: { id: 111 },
		} );

		SetupAccounts.mockReturnValue( null );

		SetupPaidAds.mockImplementation( ( { onSubmit } ) => {
			submitSetupPaidAds = onSubmit;
			return null;
		} );

		AssetGroup.mockImplementation( ( { onSkipClick } ) => {
			skipAssetGroup = onSkipClick;
			return null;
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
		submitSetupPaidAds = undefined;
		skipAssetGroup = undefined;
	} );

	async function applyOfferAndContinue() {
		await act( async () =>
			submitSetupPaidAds( { hasAppliedIncentive: true } )
		);

		// Wait for the "Create your campaign" step to be rendered.
		await waitFor( () => {
			expect( skipAssetGroup ).toBeDefined();
		} );
	}

	it( 'Should add the applied offer query arg when finishing with the same Ads account', async () => {
		render( <SavedAdsOnlySetupStepper savedStep="2" /> );

		await applyOfferAndContinue();
		await act( async () => skipAssetGroup() );

		expect( getDashboardUrl ).toHaveBeenCalledWith( {
			guide: GUIDE_NAMES.SUBMISSION_SUCCESS,
			...INCENTIVE_APPLIED_QUERY,
		} );
	} );

	it( 'Should not add the applied offer query arg when the Ads account changed after applying the offer', async () => {
		const { rerender } = render(
			<SavedAdsOnlySetupStepper savedStep="2" />
		);

		await applyOfferAndContinue();

		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: { id: 222 },
		} );
		rerender( <SavedAdsOnlySetupStepper savedStep="2" /> );

		await act( async () => skipAssetGroup() );

		expect( getDashboardUrl ).toHaveBeenCalledWith( {
			guide: GUIDE_NAMES.SUBMISSION_SUCCESS,
		} );
	} );
} );
