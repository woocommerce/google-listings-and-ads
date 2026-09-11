/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	setOnboardedMerchant,
	clearOnboardedMerchant,
	clearCompletedAdsSetup,
	clearServiceBasedMerchant,
	createSimpleProduct,
} from '../../utils/api';
import { getClassicProductEditorUtils } from '../../utils/product-editor';
import MockRequests from '../../utils/mock-requests';
import AnalyticsOverviewPage, {
	PRIMARY_AFTER,
	PRIMARY_BEFORE,
	UP_RANGE_AFTER,
	UP_RANGE_BEFORE,
	METRICS_CASE,
} from '../../utils/pages/analytics-overview';

// Copy matrix, keyed by matched case × connection state.
const COPY = {
	[ METRICS_CASE.REVENUE ]: {
		notOnboarded: {
			headline: 'Sales a bit slow? Reach more shoppers with Google.',
			body: 'Sync your catalog with Google and grow back your sales by reaching new shoppers right when they are searching to buy.',
			cta: 'Get started',
		},
		connected: {
			headline:
				'Sales a bit slow? Give your products a boost with Google.',
			body: 'Launch a Google Ads campaign and grow back your sales by reaching shoppers who are ready to buy.',
			cta: 'Launch a campaign',
		},
	},
	[ METRICS_CASE.PRODUCTS ]: {
		notOnboarded: {
			headline:
				'Selling fewer items than usual? Reach more shoppers with Google.',
			body: 'Sync your catalog with Google and sell more of your products by reaching new shoppers right when they are searching to buy.',
			cta: 'Get started',
		},
		connected: {
			headline:
				'Selling fewer items than usual? Give your products a boost with Google.',
			body: 'Launch a Google Ads campaign and sell more of your products by reaching shoppers who are ready to buy.',
			cta: 'Launch a campaign',
		},
	},
};

// CTA destinations by connection state.
const CTA_PATH = {
	notOnboarded: 'path=%2Fgoogle%2Fsetup-mc',
	connected: 'path=%2Fgoogle%2Fsetup-ads',
};

// Referrer attribution carried on the CTA.
const REFERRER_TYPE = 'in_product_placements';
const REFERRER_ID = {
	notOnboarded: 'analytics-overview-promo-get-started',
	connected: 'analytics-overview-promo-launch-campaign',
};

// Tracking events.
const EVENT = {
	shown: 'gla_analytics_overview_promo_shown',
	getStartedClick: 'gla_analytics_overview_promo_get_started_click',
	launchCampaignClick: 'gla_analytics_overview_promo_launch_campaign_click',
	dismissClick: 'gla_analytics_overview_promo_dismiss_click',
};

const PRIMARY_RANGE = { after: PRIMARY_AFTER, before: PRIMARY_BEFORE };
const UP_RANGE = { after: UP_RANGE_AFTER, before: UP_RANGE_BEFORE };

/**
 * Put the merchant into the not-onboarded state (plugin installed, not connected to G4W).
 *
 * @return {Promise<void>}
 */
async function setNotOnboarded() {
	await clearOnboardedMerchant();
	await clearServiceBasedMerchant();
}

/**
 * Put the merchant into the connected state with the given recent ad spend.
 *
 * @param {AnalyticsOverviewPage} overview Page object providing the ad-spend mock.
 * @param {number}                spend    Ad spend to report for the last 14 days.
 * @return {Promise<void>}
 */
async function setConnected( overview, spend ) {
	await clearServiceBasedMerchant();
	await setOnboardedMerchant();
	await overview.mockAdSpend( spend );
}

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

test.describe( 'Analytics Overview promo', () => {
	/**
	 * The section is registered on Analytics → Overview and renders when eligible.
	 */
	test.describe( 'Section registration', () => {
		let page = null;
		let overview = null;

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
			overview = new AnalyticsOverviewPage( page );
			await overview.mockNotDismissed();
			await setNotOnboarded();
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );
		} );

		test.afterAll( async () => {
			await clearOnboardedMerchant();
			await page.close();
		} );

		test( 'renders the promo section on the Overview tab', async () => {
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toBeVisible();
		} );

		test( 'does not render the promo on other Analytics sub-tabs', async () => {
			await page.goto(
				'/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fproducts'
			);
			await overview.waitForOverviewReady();
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toHaveCount( 0 );
		} );
	} );

	/**
	 * Metrics-down gating and live show/hide on date-range switch.
	 */
	test.describe( 'Metrics-down gating', () => {
		let page = null;
		let overview = null;

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
			overview = new AnalyticsOverviewPage( page );
			await overview.mockNotDismissed();
			await setNotOnboarded();
			// Down for the primary range, up for the alternate range.
			await overview.mockMetricsDown(
				METRICS_CASE.REVENUE,
				PRIMARY_AFTER
			);
			await overview.mockMetricsUp( UP_RANGE_AFTER );
		} );

		test.afterAll( async () => {
			await overview.clearReportStats();
			await clearOnboardedMerchant();
			await page.close();
		} );

		test( 'shows the card when metrics are down for the selected period', async () => {
			await overview.goto( PRIMARY_RANGE );
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toBeVisible();
		} );

		test( 'hides the card when metrics are up for the selected period', async () => {
			await overview.goto( UP_RANGE );
			await overview.waitForOverviewReady();
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toHaveCount( 0 );
		} );

		test( 'toggles live when the date range switches, without a full reload', async () => {
			await overview.goto( PRIMARY_RANGE );
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toBeVisible();

			// Marker to detect a full reload: it survives an in-place SPA navigation only.
			await page.evaluate( () => {
				window.__glaReloadMarker = 'kept';
			} );

			await overview.switchDateRangeInPlace( UP_RANGE );

			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toHaveCount( 0 );

			const marker = await page.evaluate(
				() => window.__glaReloadMarker
			);
			expect( marker ).toBe( 'kept' );
		} );

		test( 'stays hidden when the selected period has no comparison data', async () => {
			await overview.clearReportStats();
			await overview.mockNoComparisonData( PRIMARY_AFTER );
			await overview.goto( PRIMARY_RANGE );
			await overview.waitForOverviewReady();
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toHaveCount( 0 );
		} );
	} );

	/**
	 * Merchant-state branches and ad-spend suppression.
	 */
	test.describe( 'Merchant-state gating', () => {
		let page = null;
		let overview = null;

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
			overview = new AnalyticsOverviewPage( page );
			await overview.mockNotDismissed();
		} );

		test.afterEach( async () => {
			await clearOnboardedMerchant();
		} );

		test.afterAll( async () => {
			await page.close();
		} );

		test( 'not-onboarded merchant sees the "Get started" CTA', async () => {
			await setNotOnboarded();
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );

			await expect( overview.getCtaButton() ).toHaveText( 'Get started' );
		} );

		test( 'connected merchant with no ad spend sees the "Launch a campaign" CTA', async () => {
			await setConnected( overview, 0 );
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );

			await expect( overview.getCtaButton() ).toHaveText(
				'Launch a campaign'
			);
		} );

		test( 'connected merchant with recent ad spend is suppressed', async () => {
			await setConnected( overview, 120 );
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );
			await overview.waitForOverviewReady();

			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toHaveCount( 0 );
		} );

		test( 'connected merchant with an INCOMPLETE account is treated as ready', async () => {
			await overview.mockConnectedIncomplete();
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );

			await expect( overview.getCtaButton() ).toHaveText(
				'Launch a campaign'
			);
		} );
	} );

	/**
	 * Copy/CTA matrix: matched case (row) × connection state (column).
	 */
	test.describe( 'Copy and CTA matrix', () => {
		let page = null;
		let overview = null;

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
			overview = new AnalyticsOverviewPage( page );
			await overview.mockNotDismissed();
		} );

		test.afterEach( async () => {
			await clearOnboardedMerchant();
		} );

		test.afterAll( async () => {
			await page.close();
		} );

		const cases = [
			{
				name: 'Case 1 (revenue) — not onboarded',
				metricsCase: METRICS_CASE.REVENUE,
				state: 'notOnboarded',
			},
			{
				name: 'Case 1 (revenue) — connected',
				metricsCase: METRICS_CASE.REVENUE,
				state: 'connected',
			},
			{
				name: 'Case 2 (products) — not onboarded',
				metricsCase: METRICS_CASE.PRODUCTS,
				state: 'notOnboarded',
			},
			{
				name: 'Case 2 (products) — connected',
				metricsCase: METRICS_CASE.PRODUCTS,
				state: 'connected',
			},
		];

		for ( const { name, metricsCase, state } of cases ) {
			test( `renders the correct copy and CTA for ${ name }`, async () => {
				if ( state === 'connected' ) {
					await setConnected( overview, 0 );
				} else {
					await setNotOnboarded();
				}
				await overview.mockMetricsDown( metricsCase );
				await overview.goto( PRIMARY_RANGE );

				const copy = COPY[ metricsCase ][ state ];
				await expect( overview.getCardHeadline() ).toHaveText(
					copy.headline
				);
				await expect(
					overview.getAnalyticsOverviewPromoSection()
				).toContainText( copy.body );

				const cta = overview.getCtaButton();
				await expect( cta ).toHaveText( copy.cta );
				await expect( cta ).toHaveAttribute(
					'href',
					new RegExp( CTA_PATH[ state ] )
				);
			} );
		}
	} );

	/**
	 * Durable dismissal.
	 */
	test.describe( 'Dismissal', () => {
		let page = null;
		let overview = null;

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
			overview = new AnalyticsOverviewPage( page );
			await overview.mockNotDismissed();
			await setNotOnboarded();
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );
		} );

		test.afterAll( async () => {
			await clearOnboardedMerchant();
			await page.close();
		} );

		test( 'hides the card immediately on dismiss and persists it', async () => {
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toBeVisible();

			// Dismissal is persisted via `@wordpress/preferences`, POSTed to the user meta.
			const persisted = page.waitForRequest(
				( request ) =>
					/\/wp\/v2\/users\/me/.test( request.url() ) &&
					request.method() === 'POST'
			);
			await overview.getDismissButton().click();
			await persisted;

			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toHaveCount( 0 );
		} );

		test( 'stays hidden after a reload', async () => {
			await overview.goto( PRIMARY_RANGE );
			await overview.waitForOverviewReady();
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toHaveCount( 0 );
		} );
	} );

	/**
	 * Tracking events and referrer-arg propagation.
	 */
	test.describe( 'Tracking', () => {
		let page = null;
		let overview = null;

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
			overview = new AnalyticsOverviewPage( page );
			await overview.installTracksSpy();
			await overview.mockNotDismissed();
			await setNotOnboarded();
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );
		} );

		test.afterAll( async () => {
			await clearOnboardedMerchant();
			await page.close();
		} );

		test( 'fires the shown event with the matched case and placement props', async () => {
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toBeVisible();

			const shown = await overview.getTrackedEvents( EVENT.shown );
			expect( shown ).toHaveLength( 1 );
			expect( shown[ 0 ].props ).toMatchObject( {
				metrics_case: METRICS_CASE.REVENUE,
			} );
			expect( shown[ 0 ].props.context ).toBeTruthy();
		} );

		test( 'CTA carries referrer args and fires the click event with its props', async () => {
			const cta = overview.getCtaButton();
			await expect( cta ).toHaveAttribute(
				'href',
				new RegExp( `referrer_type=${ REFERRER_TYPE }` )
			);
			await expect( cta ).toHaveAttribute(
				'href',
				new RegExp( `referrer_id=${ REFERRER_ID.notOnboarded }` )
			);

			await cta.click();

			const clicks = await overview.getTrackedEvents(
				EVENT.getStartedClick
			);
			expect( clicks ).toHaveLength( 1 );
			expect( clicks[ 0 ].props ).toMatchObject( {
				metrics_case: METRICS_CASE.REVENUE,
			} );
			expect( clicks[ 0 ].props.href ).toContain( 'setup-mc' );
		} );

		test( 'referrer args survive the hop to onboarding and reach downstream events', async () => {
			await overview.goto( PRIMARY_RANGE );
			await overview.getCtaButton().click();
			await page.waitForURL( /setup-mc/ );

			expect( page.url() ).toContain(
				`referrer_type=${ REFERRER_TYPE }`
			);
			expect( page.url() ).toContain(
				`referrer_id=${ REFERRER_ID.notOnboarded }`
			);

			// Downstream tracking events on the onboarding screen carry the referrer attribution,
			// so the conversion attributes back to the placement.
			await expect
				.poll( async () => {
					const events = await overview.getTrackedEvents();
					return events.some(
						( event ) =>
							event.props?.referrer_type === REFERRER_TYPE &&
							event.props?.referrer_id ===
								REFERRER_ID.notOnboarded
					);
				} )
				.toBe( true );
		} );

		test( 'fires the dismiss event with the matched-case prop', async () => {
			await overview.goto( PRIMARY_RANGE );
			await overview.getDismissButton().click();

			const dismissed = await overview.getTrackedEvents(
				EVENT.dismissClick
			);
			expect( dismissed ).toHaveLength( 1 );
			expect( dismissed[ 0 ].props ).toMatchObject( {
				metrics_case: METRICS_CASE.REVENUE,
			} );
		} );

		test( 'referrer args reach campaign creation and its downstream events for a connected merchant', async () => {
			await setConnected( overview, 0 );
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );

			await overview.getCtaButton().click();
			await page.waitForURL( /setup-ads/ );

			expect( page.url() ).toContain(
				`referrer_type=${ REFERRER_TYPE }`
			);
			expect( page.url() ).toContain(
				`referrer_id=${ REFERRER_ID.connected }`
			);

			// The campaign-creation screen is the conversion end of the flow; its tracking events
			// carry the referrer attribution, so the created campaign attributes to the placement.
			await expect
				.poll( async () => {
					const events = await overview.getTrackedEvents();
					return events.some(
						( event ) =>
							event.props?.referrer_type === REFERRER_TYPE &&
							event.props?.referrer_id === REFERRER_ID.connected
					);
				} )
				.toBe( true );

			await clearOnboardedMerchant();
		} );
	} );

	/**
	 * No regression to the core Overview sections.
	 */
	test.describe( 'No regression to Overview sections', () => {
		let page = null;
		let overview = null;

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
			overview = new AnalyticsOverviewPage( page );
			await overview.mockNotDismissed();
			await setNotOnboarded();
			await overview.mockMetricsDown( METRICS_CASE.REVENUE );
			await overview.goto( PRIMARY_RANGE );
		} );

		test.afterAll( async () => {
			await clearOnboardedMerchant();
			await page.close();
		} );

		test( 'renders the promo alongside the core Overview report', async () => {
			await expect(
				overview.getAnalyticsOverviewPromoSection()
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-filters' )
			).toBeVisible();
			// The performance summary and charts are core Overview sections that must stay intact.
			await expect(
				page.locator( '.woocommerce-summary' )
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-chart' ).first()
			).toBeVisible();
		} );
	} );

	/**
	 * No regression to the Phase 1 in-product placement on the product editor.
	 */
	test.describe( 'No regression to Phase 1 placements', () => {
		let page = null;
		let editorUtils = null;
		let mockRequests = null;
		let productId = null;

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
			editorUtils = getClassicProductEditorUtils( page );
			mockRequests = new MockRequests( page );
			await setOnboardedMerchant();
			await clearCompletedAdsSetup();
			await mockRequests.mockJetpackConnected();
			await mockRequests.mockGoogleConnected();
			productId = await createSimpleProduct();
		} );

		test.afterAll( async () => {
			await clearOnboardedMerchant();
			await clearCompletedAdsSetup();
			await clearServiceBasedMerchant();
			await page.close();
		} );

		test( 'renders the channel visibility placement on the product editor', async () => {
			await editorUtils.gotoEditProductPage( productId );
			await expect(
				editorUtils.getChannelVisibilityMetaBoxContent()
			).toBeVisible();
		} );
	} );
} );
