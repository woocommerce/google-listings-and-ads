/**
 * Internal dependencies
 */
import {
	getActionedCampaigns,
	upsertActionedCampaign,
} from './actionedCampaignsCache';
import { LOCAL_STORAGE_KEYS, DAY_IN_SECONDS } from '~/constants';
import localStorage from '~/utils/localStorage';

jest.mock( '~/utils/localStorage', () => ( {
	get: jest.fn(),
	set: jest.fn(),
} ) );

describe( 'actionedCampaignsCache', () => {
	beforeEach( () => {
		localStorage.get.mockReset();
		jest.clearAllMocks();
		jest.spyOn( Date, 'now' ).mockImplementation( () => 100000 );
	} );

	describe( 'getActionedCampaigns', () => {
		it( 'returns empty array if no campaigns in localStorage', () => {
			localStorage.get.mockReturnValue( undefined );
			expect( getActionedCampaigns() ).toEqual( [] );
		} );

		it( 'returns empty array if campaigns object is empty', () => {
			localStorage.get.mockReturnValue( '{}' );
			expect( getActionedCampaigns() ).toEqual( [] );
		} );

		it( 'filters out expired campaigns and updates localStorage', () => {
			const now = Date.now();
			const validId = 'valid';
			const expiredId = 'expired';
			const campaigns = {
				[ validId ]: now + 10000,
				[ expiredId ]: now - 10000,
			};
			localStorage.get.mockReturnValue( JSON.stringify( campaigns ) );
			const result = getActionedCampaigns();
			expect( result ).toEqual( [ validId ] );
			expect( localStorage.set ).toHaveBeenCalledWith(
				LOCAL_STORAGE_KEYS.RAISE_BUDGET_RECOMMENDATIONS_ACTIONED_CAMPAIGNS,
				JSON.stringify( { [ validId ]: campaigns[ validId ] } )
			);
		} );

		it( 'returns all valid campaign ids', () => {
			const now = Date.now();
			const campaigns = {
				a: now + 10000,
				b: now + 20000,
			};
			localStorage.get.mockReturnValue( JSON.stringify( campaigns ) );
			expect( getActionedCampaigns().sort() ).toEqual( [ 'a', 'b' ] );
			expect( localStorage.set ).not.toHaveBeenCalled();
		} );

		it( 'handles invalid JSON gracefully', () => {
			localStorage.get.mockReturnValue( 'not-json' );
			expect( getActionedCampaigns() ).toEqual( [] );
		} );
	} );

	describe( 'upsertActionedCampaign', () => {
		it( 'inserts a new campaign with correct expiry', () => {
			localStorage.get.mockReturnValue( '{}' );
			const campaignId = 'new-campaign';
			const before = Date.now();
			upsertActionedCampaign( campaignId );
			const setArgs = localStorage.set.mock.calls[ 0 ][ 1 ];
			const campaigns = JSON.parse( setArgs );
			expect( Object.keys( campaigns ) ).toContain( campaignId );
			const expiry = campaigns[ campaignId ];
			expect( expiry ).toBeGreaterThanOrEqual(
				before + DAY_IN_SECONDS * 1000
			);
			expect( localStorage.set ).toHaveBeenCalledWith(
				LOCAL_STORAGE_KEYS.RAISE_BUDGET_RECOMMENDATIONS_ACTIONED_CAMPAIGNS,
				expect.any( String )
			);
		} );

		it( 'updates expiry for existing campaign', () => {
			const oldExpiry = Date.now() - 10000;
			const campaignId = 'existing-campaign';
			localStorage.get.mockReturnValue(
				JSON.stringify( { [ campaignId ]: oldExpiry } )
			);
			upsertActionedCampaign( campaignId );
			const setArgs = localStorage.set.mock.calls[ 0 ][ 1 ];
			const campaigns = JSON.parse( setArgs );
			expect( campaigns[ campaignId ] ).toBeGreaterThan( oldExpiry );
		} );

		it( 'handles invalid JSON in localStorage', () => {
			localStorage.get.mockReturnValue( 'bad-json' );
			const campaignId = 'bad-campaign';
			upsertActionedCampaign( campaignId );
			const setArgs = localStorage.set.mock.calls[ 0 ][ 1 ];
			const campaigns = JSON.parse( setArgs );
			expect( Object.keys( campaigns ) ).toContain( campaignId );
		} );
	} );
} );
