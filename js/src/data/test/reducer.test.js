/**
 * External dependencies
 */
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';
import { deepFreeze, prepareImmutableStateWithRefCheck } from './__helpers__';

describe( 'reducer', () => {
	let defaultState;
	let prepareState;

	beforeEach( () => {
		defaultState = deepFreeze( {
			general: {
				version: null,
				mcId: null,
				adsId: null,
			},
			mc: {
				target_audience: null,
				countries: null,
				continents: null,
				policy_check: null,
				shipping: {
					rates: [],
					times: [],
				},
				settings: null,
				accounts: {
					jetpack: null,
					google: null,
					mc: null,
					ads: null,
					existing_mc: null,
					existing_ads: null,
					ads_billing_status: null,
					google_access: null,
				},
				mapping: {
					attributes: [],
					rules: { items: [], pages: null, total: null },
					sources: {}, // Todo: Change to [] after finishing the fix in backend
				},
				contact: null,
			},
			ads_campaigns: null,
			all_ads_campaigns: null,
			campaign_asset_groups: {},
			mc_setup: null,
			mc_review_request: {
				issues: null,
				cooldown: null,
				status: null,
				reviewEligibleRegions: [],
			},
			mc_product_statistics: null,
			mc_issues: {
				account: null,
				product: null,
			},
			mc_product_feed: null,
			report: {},
			store_categories: [],
			tours: {},
			ads: {
				accountStatus: {
					hasAccess: null,
					inviteLink: null,
					step: null,
				},
				budgetRecommendations: {},
			},
		} );

		prepareState = prepareImmutableStateWithRefCheck.bind(
			null,
			defaultState
		);
	} );

	describe( 'General reducer behaviors', () => {
		it( 'should return default state by default', () => {
			const state = reducer( undefined, {} );

			expect( state ).toEqual( defaultState );
		} );

		it( 'when no action is matched, should return the same reference of state as the passed-in `state` parameter', () => {
			const originalState = {};

			expect( reducer( originalState, {} ) ).toBe( originalState );
		} );

		it( 'when action is not required to change state, should return the same reference of state as the passed-in `state` parameter', () => {
			const originalState = {};
			const state = reducer( originalState, {
				type: TYPES.DISCONNECT_ACCOUNTS_ALL,
			} );

			expect( state ).toBe( originalState );
		} );
	} );

	describe( 'General', () => {
		const path = 'general';

		it( 'should hydrate the prefetched data', () => {
			const data = {
				version: '2.3.4',
				mcId: 123456789,
				adsId: 987654321,
			};
			const state = reducer( prepareState(), {
				type: TYPES.HYDRATE_PREFETCHED_DATA,
				data,
			} );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( `${ path }.version`, data.version );
			expect( state ).toHaveProperty( `${ path }.mcId`, data.mcId );
			expect( state ).toHaveProperty( `${ path }.adsId`, data.adsId );
		} );
	} );

	describe( 'Merchant center shipping rate at `mc.shipping.rates`', () => {
		const path = 'mc.shipping.rates';

		it( 'should return with received shipping rates', () => {
			const action = {
				type: TYPES.RECEIVE_SHIPPING_RATES,
				shippingRates: [
					{
						id: '1',
						country: 'US',
						currency: 'USD',
						rate: 4.99,
						options: {},
					},
					{
						id: '2',
						country: 'AU',
						currency: 'USD',
						rate: 25,
						options: {},
					},
				],
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.shippingRates );
		} );

		it( 'should return with upserted shipping rates by matching `countryCode`', () => {
			const originalState = prepareState( path, [
				{
					id: '1',
					country: 'US',
					currency: 'USD',
					rate: 4.99,
					options: {},
				},
				{
					id: '2',
					country: 'CA',
					currency: 'USD',
					rate: 25,
					options: {},
				},
			] );
			const action = {
				type: TYPES.UPSERT_SHIPPING_RATES,
				shippingRates: [
					{
						id: '2',
						country: 'CA',
						currency: 'USD',
						rate: 12,
						options: {},
					},
					{
						id: '3',
						country: 'JP',
						currency: 'USD',
						rate: 12,
						options: {},
					},
				],
			};

			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					id: '1',
					country: 'US',
					currency: 'USD',
					rate: 4.99,
					options: {},
				},
				{
					id: '2',
					country: 'CA',
					currency: 'USD',
					rate: 12,
					options: {},
				},
				{
					id: '3',
					country: 'JP',
					currency: 'USD',
					rate: 12,
					options: {},
				},
			] );
		} );

		it( 'should return with remaining shipping rates after deleting specific items by matching `countryCode`', () => {
			const originalState = prepareState( path, [
				{
					id: '1',
					country: 'US',
					currency: 'USD',
					rate: 4.99,
					options: {},
				},
				{
					id: '2',
					country: 'CA',
					currency: 'USD',
					rate: 25,
					options: {},
				},
			] );
			const action = {
				type: TYPES.DELETE_SHIPPING_RATES,
				ids: [ '2' ],
			};

			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					id: '1',
					country: 'US',
					currency: 'USD',
					rate: 4.99,
					options: {},
				},
			] );
		} );
	} );

	describe( 'Merchant center shipping time at `mc.shipping.times`', () => {
		const path = 'mc.shipping.times';

		it( 'should return with received shipping times', () => {
			const action = {
				type: TYPES.RECEIVE_SHIPPING_TIMES,
				shippingTimes: [
					{
						countryCode: 'US',
						time: 7,
					},
					{
						countryCode: 'CA',
						time: 12,
					},
				],
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.shippingTimes );
		} );

		it( 'should return with upserted shipping times by matching `countryCode`', () => {
			const originalState = prepareState( path, [
				{
					countryCode: 'US',
					time: 7,
				},
				{
					countryCode: 'CA',
					time: 12,
				},
			] );
			const action = {
				type: TYPES.UPSERT_SHIPPING_TIMES,
				shippingTime: {
					countryCodes: [ 'JP', 'CA' ],
					time: 15,
				},
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					countryCode: 'US',
					time: 7,
				},
				{
					countryCode: 'CA',
					time: 15,
				},
				{
					countryCode: 'JP',
					time: 15,
				},
			] );
		} );

		it( 'should return with remaining shipping times after deleting specific items by matching `countryCode`', () => {
			const originalState = prepareState( path, [
				{
					countryCode: 'US',
					time: 7,
				},
				{
					countryCode: 'CA',
					time: 12,
				},
				{
					countryCode: 'JP',
					time: 15,
				},
			] );
			const action = {
				type: TYPES.DELETE_SHIPPING_TIMES,
				countryCodes: [ 'US', 'JP' ],
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					countryCode: 'CA',
					time: 12,
				},
			] );
		} );
	} );

	describe( 'Merchant Center settings', () => {
		const path = 'mc.settings';

		it( 'should return with received Merchant Center settings', () => {
			const action = {
				type: TYPES.SAVE_SETTINGS,
				settings: {
					settingA: 'A',
					SettingB: 'B',
				},
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.settings );
		} );

		it( 'should return with partially updated Merchant Center settings', () => {
			const originalState = prepareState(
				path,
				{
					existingSettingA: 'should be kept',
					existingSettingB: 'should be updated from old value',
				},
				true
			);
			const action = {
				type: TYPES.SAVE_SETTINGS,
				settings: {
					existingSettingB: 'should be updated to new value',
					existingSettingC: 'should be added',
				},
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, {
				existingSettingA: 'should be kept',
				existingSettingB: 'should be updated to new value',
				existingSettingC: 'should be added',
			} );
		} );
	} );

	describe( 'Google Ads account connection', () => {
		const path = 'mc.accounts.ads';

		it( 'should return with received Google Ads account connection', () => {
			const action = {
				type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
				account: { id: 123456789 },
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.account );
		} );

		it( 'should return with default Google Ads account connection when getting disconnect action', () => {
			const originalState = prepareState( path, { id: 123456789 }, true );
			const action = { type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS };
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, get( defaultState, path ) );
		} );
	} );

	describe( 'Countries and continents supported by Merchant Center', () => {
		it( 'should return with received countries and continents', () => {
			const data = {
				countries: {
					CA: { currency: 'CAD', name: 'Canada' },
					US: { currency: 'USD', name: 'United States' },
				},
				continents: {
					NA: {
						name: 'North America',
						countries: [ 'CA', 'US' ],
					},
				},
			};
			const action = {
				type: TYPES.RECEIVE_MC_COUNTRIES_AND_CONTINENTS,
				data,
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( 'mc.countries', data.countries );
			expect( state ).toHaveProperty( 'mc.continents', data.continents );
		} );
	} );

	describe( 'Ads campaigns', () => {
		const path = 'ads_campaigns';
		const pathAllAds = 'all_ads_campaigns';

		it( 'should return with received ads campaigns', () => {
			const action = {
				type: TYPES.RECEIVE_ADS_CAMPAIGNS,
				adsCampaigns: [ { id: 123 }, { id: 456 } ],
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, action.adsCampaigns );
		} );

		it( 'should push the created ads campaign and return with updated ads campaigns', () => {
			const createdToInitialState = reducer( prepareState(), {
				type: TYPES.CREATE_ADS_CAMPAIGN,
				createdCampaign: { id: 123 },
			} );
			const createdToLoadedState = reducer( createdToInitialState, {
				type: TYPES.CREATE_ADS_CAMPAIGN,
				createdCampaign: { id: 456 },
			} );

			createdToInitialState.assertConsistentRef();
			createdToLoadedState.assertConsistentRef();
			expect( createdToInitialState ).toHaveProperty( path, [
				{ id: 123 },
			] );
			expect( createdToLoadedState ).toHaveProperty( path, [
				{ id: 123 },
				{ id: 456 },
			] );
		} );

		it( 'should patch the given data properties and return with updated ads campaign by matching `id`', () => {
			const originalState = prepareState( path, [
				{
					id: 123,
					status: 'paused',
					name: 'how do you turn this on',
					amount: 50,
				},
				{
					id: 456,
					status: 'enabled',
					name: 'alpaca simulator',
					amount: 999,
				},
			] );
			const action = {
				type: TYPES.UPDATE_ADS_CAMPAIGN,
				id: 123,
				data: { name: 'robin hood', amount: 10000, status: 'enabled' },
			};
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{
					id: 123,
					status: 'enabled',
					name: 'robin hood',
					amount: 10000,
				},
				{
					id: 456,
					status: 'enabled',
					name: 'alpaca simulator',
					amount: 999,
				},
			] );
		} );

		it( 'should return with remaining ads campaigns after deleting specific one by matching `id`', () => {
			const originalState = prepareState( path, [
				{ id: 123 },
				{ id: 456 },
				{ id: 789 },
			] );
			const action = { type: TYPES.DELETE_ADS_CAMPAIGN, id: 456 };
			const state = reducer( originalState, action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( path, [
				{ id: 123 },
				{ id: 789 },
			] );
		} );

		it( 'should return with all ads campaigns if exclude removed is false', () => {
			const action = {
				type: TYPES.RECEIVE_ADS_CAMPAIGNS,
				adsCampaigns: [ { id: 123 }, { id: 456 } ],
				query: { exclude_removed: false },
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( pathAllAds, action.adsCampaigns );
		} );
	} );

	describe( 'Campaign asset groups', () => {
		const path = 'campaign_asset_groups';

		it( 'should store asset groups individually by the given `campaignId` and return with received asset groups', () => {
			const groupsA = [ { id: 1 }, { id: 2 } ];
			const groupsB = [ { id: 3 }, { id: 4 } ];
			const firstState = reducer( prepareState(), {
				type: TYPES.RECEIVE_CAMPAIGN_ASSET_GROUPS,
				campaignId: 123,
				assetGroups: groupsA,
			} );

			firstState.assertConsistentRef();
			expect( firstState ).toHaveProperty( `${ path }.123`, groupsA );

			// Store other asset groups individually.
			const secondState = reducer( firstState, {
				type: TYPES.RECEIVE_CAMPAIGN_ASSET_GROUPS,
				campaignId: 456,
				assetGroups: groupsB,
			} );

			secondState.assertConsistentRef();
			expect( secondState ).toHaveProperty( `${ path }.123`, groupsA );
			expect( secondState ).toHaveProperty( `${ path }.456`, groupsB );
		} );

		it( 'should push the newly created asset group into the specific asset group by the given `campaignId` and return with updated asset groups', () => {
			const groupA = { id: 1 };
			const groupB = { id: 2 };
			const firstState = reducer( prepareState(), {
				type: TYPES.CREATE_CAMPAIGN_ASSET_GROUP,
				campaignId: 123,
				assetGroup: groupA,
			} );

			firstState.assertConsistentRef();
			expect( firstState ).toHaveProperty( `${ path }.123`, [ groupA ] );

			const secondState = reducer( firstState, {
				type: TYPES.CREATE_CAMPAIGN_ASSET_GROUP,
				campaignId: 123,
				assetGroup: groupB,
			} );

			secondState.assertConsistentRef();
			expect( secondState ).toHaveProperty( `${ path }.123`, [
				groupA,
				groupB,
			] );
		} );
	} );

	describe( 'Merchant Center issues', () => {
		const basePath = 'mc_issues';
		const productPath = basePath + '.product';
		const accountPath = basePath + '.account';

		it( 'Issues are split between Account and Product without collision', () => {
			const accountIssuesPage1 = [ '#1-Account', '#2-Account' ];
			const productIssuesPage1 = [ '#1-Product', '#2-Product' ];
			const accountIssuesPage2 = [ '#3-Account', '#4-Account' ];

			const accountIssuesState = reducer( prepareState(), {
				type: TYPES.RECEIVE_MC_ISSUES,
				query: { page: 1, per_page: 2, issue_type: 'account' },
				data: { total: 5, issues: accountIssuesPage1 },
			} );

			const productIssuesState = reducer( accountIssuesState, {
				type: TYPES.RECEIVE_MC_ISSUES,
				query: { page: 1, per_page: 2, issue_type: 'product' },
				data: { total: 3, issues: productIssuesPage1 },
			} );

			const accountIssuesStatePage2 = reducer( productIssuesState, {
				type: TYPES.RECEIVE_MC_ISSUES,
				query: { page: 2, per_page: 2, issue_type: 'account' },
				data: { total: 5, issues: accountIssuesPage2 },
			} );

			accountIssuesState.assertConsistentRef();
			productIssuesState.assertConsistentRef();
			accountIssuesStatePage2.assertConsistentRef();

			expect( accountIssuesState ).toHaveProperty( accountPath, {
				total: 5,
				issues: accountIssuesPage1,
			} );

			expect( accountIssuesState ).toHaveProperty( productPath, null );

			expect( productIssuesState ).toHaveProperty( accountPath, {
				total: 5,
				issues: accountIssuesPage1,
			} );

			expect( productIssuesState ).toHaveProperty( productPath, {
				total: 3,
				issues: productIssuesPage1,
			} );

			expect( accountIssuesStatePage2 ).toHaveProperty( accountPath, {
				total: 5,
				issues: [ ...accountIssuesPage1, ...accountIssuesPage2 ],
			} );

			expect( accountIssuesStatePage2 ).toHaveProperty( productPath, {
				total: 3,
				issues: productIssuesPage1,
			} );
		} );

		it.each( [ 'account', 'product' ] )(
			'Issues of type %s should only allow receiving pagination data sequentially from the first page and return with received issues array and total number of issues',
			( issueType ) => {
				const path = `${ basePath }.${ issueType }`;
				const pageOneState = reducer( prepareState(), {
					type: TYPES.RECEIVE_MC_ISSUES,
					query: { page: 1, per_page: 2, issue_type: issueType },
					data: { total: 5, issues: [ '#1', '#2' ] },
				} );

				const pageTwoState = reducer( pageOneState, {
					type: TYPES.RECEIVE_MC_ISSUES,
					query: { page: 2, per_page: 2, issue_type: issueType },
					data: { total: 5, issues: [ '#3', '#4' ] },
				} );

				const pageThreeState = reducer( pageTwoState, {
					type: TYPES.RECEIVE_MC_ISSUES,
					query: { page: 3, per_page: 2, issue_type: issueType },
					data: { total: 5, issues: [ '#5' ] },
				} );

				pageOneState.assertConsistentRef();
				pageTwoState.assertConsistentRef();
				pageThreeState.assertConsistentRef();

				expect( pageOneState ).toHaveProperty( path, {
					total: 5,
					issues: [ '#1', '#2' ],
				} );
				expect( pageTwoState ).toHaveProperty( path, {
					total: 5,
					issues: [ '#1', '#2', '#3', '#4' ],
				} );
				expect( pageThreeState ).toHaveProperty( path, {
					total: 5,
					issues: [ '#1', '#2', '#3', '#4', '#5' ],
				} );
			}
		);
	} );

	describe( 'Merchant Center product feed', () => {
		const path = 'mc_product_feed';

		it( 'should store paginated data by `query` and return with received product feed, total number of product feed and some query data', () => {
			const pageOneState = reducer( prepareState(), {
				type: TYPES.RECEIVE_MC_PRODUCT_FEED,
				query: {
					order: 'asc',
					orderby: 'title',
					per_page: 2,
					page: 1,
				},
				data: {
					total: 7,
					products: [ '#1', '#2' ],
				},
			} );
			// Support for storing product feed regardless of pagination loading order.
			const pageFourState = reducer( pageOneState, {
				type: TYPES.RECEIVE_MC_PRODUCT_FEED,
				query: {
					order: 'asc',
					orderby: 'title',
					per_page: 2,
					page: 4,
				},
				data: {
					total: 7,
					products: [ '#7' ],
				},
			} );

			pageOneState.assertConsistentRef();
			pageFourState.assertConsistentRef();
			expect( pageOneState ).toHaveProperty( path, {
				order: 'asc',
				orderby: 'title',
				per_page: 2,
				total: 7,
				pages: { 1: [ '#1', '#2' ] },
			} );
			expect( pageFourState ).toHaveProperty( path, {
				order: 'asc',
				orderby: 'title',
				per_page: 2,
				total: 7,
				pages: { 1: [ '#1', '#2' ], 4: [ '#7' ] },
			} );
		} );

		it.each( [
			[ 'order', 'desc' ],
			[ 'orderby', 'visible' ],
			[ 'per_page', 5 ],
		] )(
			'when the `query.%s` is changed, should discard `pages` and return with received product feed',
			( key, value ) => {
				const baseQuery = {
					order: 'asc',
					orderby: 'title',
					per_page: 2,
				};
				const initValue = {
					...baseQuery,
					total: 7,
					pages: { 1: [ '#1', '#2' ], 4: [ '#7' ] },
				};
				const originalState = prepareState( path, initValue, [
					path,
					`${ path }.pages`,
				] );
				const action = {
					type: TYPES.RECEIVE_MC_PRODUCT_FEED,
					query: {
						...baseQuery,
						[ key ]: value,
						page: 2,
					},
					data: {
						total: 7,
						products: [ '#3', '#4' ],
					},
				};
				const state = reducer( originalState, action );

				state.assertConsistentRef();
				expect( state ).toHaveProperty( path, {
					...baseQuery,
					[ key ]: value,
					total: 7,
					pages: { 2: [ '#3', '#4' ] },
				} );
			}
		);
	} );

	describe( 'Reports of programs and products', () => {
		const path = 'report';

		it( 'should store paginated data by the stringified `reportKey`, which contains JSON syntax', () => {
			const reportKey =
				'programs:free:{"after":"2021-01-01","before":"2021-01-07","fields":["sales","conversions","spend","clicks","impressions"],"interval":"day","order":"desc","orderby":"sales"}';
			const state = reducer( prepareState(), {
				type: TYPES.RECEIVE_REPORT,
				reportKey,
				data: '#1',
			} );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( [ path, reportKey ], '#1' );
		} );

		it( 'should store paginated data by `reportKey` and return with received report data', () => {
			const pageOneState = reducer( prepareState(), {
				type: TYPES.RECEIVE_REPORT,
				reportKey: 'key1',
				data: '#1',
			} );
			// Support for storing report data regardless of pagination loading order.
			const pageFourState = reducer( pageOneState, {
				type: TYPES.RECEIVE_REPORT,
				reportKey: 'key4',
				data: '#4',
			} );

			pageOneState.assertConsistentRef();
			pageFourState.assertConsistentRef();
			expect( pageOneState ).toHaveProperty( `${ path }.key1`, '#1' );
			expect( pageFourState ).toHaveProperty( `${ path }.key1`, '#1' );
			expect( pageFourState ).toHaveProperty( `${ path }.key4`, '#4' );
		} );
	} );

	describe( 'Tours', () => {
		const path = 'tours';

		it( 'should receive a tour', () => {
			const tour = { id: 'test', checked: false };
			const state = reducer( prepareState(), {
				type: TYPES.RECEIVE_TOUR,
				tour,
			} );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( [ path, tour.id ], tour );
		} );

		it( 'should upsert a tour', () => {
			const tour = { id: 'test', checked: false };
			const tourUpdated = { id: 'test', checked: true };
			const prevState = reducer( prepareState(), {
				type: TYPES.RECEIVE_TOUR,
				tour,
			} );

			const state = reducer( prevState, {
				type: TYPES.UPSERT_TOUR,
				tour: tourUpdated,
			} );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( [ path, tour.id ], tourUpdated );
		} );
	} );

	describe( 'Ads Budget Recommendations', () => {
		const path = 'ads.budgetRecommendations';

		it( 'should receive a budget recommendation', () => {
			const recommendation = {
				countryCodesKey: 'mu_sg',
				currency: 'MUR',
				recommendations: [
					{
						country: 'MU',
						daily_budget: 15,
					},
					{
						country: 'SG',
						daily_budget: 10,
					},
				],
			};

			const action = {
				type: TYPES.RECEIVE_ADS_BUDGET_RECOMMENDATIONS,
				...recommendation,
			};
			const state = reducer( prepareState(), action );

			state.assertConsistentRef();
			expect( state ).toHaveProperty( `${ path }.mu_sg`, {
				currency: recommendation.currency,
				recommendations: recommendation.recommendations,
			} );
		} );
	} );

	describe( 'Remaining actions simply update the data payload to the specific path of state and return the updated state', () => {
		// The readability is better than applying the formatting here.
		/* eslint-disable prettier/prettier */
		// prettier-ignore
		const argumentsTuples = [
			[ TYPES.RECEIVE_SETTINGS, 'settings', 'mc.settings' ],
			[ TYPES.RECEIVE_ACCOUNTS_JETPACK, 'account', 'mc.accounts.jetpack' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE, 'account', 'mc.accounts.google' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_ACCESS, 'data', 'mc.accounts.google_access' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC, 'account', 'mc.accounts.mc' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC_EXISTING, 'accounts', 'mc.accounts.existing_mc' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_BILLING_STATUS, 'billingStatus', 'mc.accounts.ads_billing_status' ],
			[ TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_EXISTING, 'accounts', 'mc.accounts.existing_ads' ],
			[ TYPES.RECEIVE_MC_CONTACT_INFORMATION, 'data', 'mc.contact' ],
			[ TYPES.RECEIVE_TARGET_AUDIENCE, 'target_audience', 'mc.target_audience' ],
			[ TYPES.SAVE_TARGET_AUDIENCE, 'target_audience', 'mc.target_audience' ],
			[ TYPES.RECEIVE_MC_SETUP, 'mcSetup', 'mc_setup' ],
			[ TYPES.RECEIVE_MC_PRODUCT_STATISTICS, 'mcProductStatistics', 'mc_product_statistics' ],
			[ TYPES.POLICY_CHECK, 'data', 'mc.policy_check' ],
			[ TYPES.RECEIVE_STORE_CATEGORIES, 'storeCategories', 'store_categories' ],
		];
		/* eslint-enable prettier/prettier */

		it.each( argumentsTuples )(
			'for type `%s`, it should return with received %s at `%s`',
			( type, key, path ) => {
				const payload = { hello: 'WordPress' };
				const action = { type, [ key ]: payload };
				const state = reducer( prepareState(), action );

				state.assertConsistentRef();
				expect( state ).toHaveProperty( path, payload );
			}
		);
	} );
} );
