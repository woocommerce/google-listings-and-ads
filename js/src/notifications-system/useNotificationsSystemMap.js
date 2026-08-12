/**
 * External dependencies
 */
import { createInterpolateElement, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useSettings from '~/hooks/useSettings';
import AppDocumentationLink from '~/components/app-documentation-link';
import { handleApiError } from '~/utils/handleError';
import {
	CONTEXT_MARKETING_OVERVIEW,
	REFERRER_TYPE_NOTIFICATION,
} from '~/utils/tracks';
import {
	getDashboardUrl,
	getProductFeedUrl,
	getSettingsUrl,
	getSetupAdsUrl,
	getWCTrackingSettingsUrl,
	getOnboardingUrl,
	getWCCouponsUrl,
} from '~/utils/urls';

const TERMS_URL =
	'https://ads.google.com/home/terms-and-conditions/incentives/';

/**
 * Appends the notification's referrer info to a CTA href, so the destination
 * flow can attribute its own tracking events back to this notification.
 *
 * Internal to this module only — not shared with other files.
 *
 * @param {string} href Original CTA destination.
 * @param {string} notificationId Notification ID to attribute the referral to.
 * @return {string} `href` with `referrer_type`/`referrer_id` query params appended.
 */
function withReferrer( href, notificationId ) {
	return addQueryArgs( href, {
		referrer_type: REFERRER_TYPE_NOTIFICATION,
		referrer_id: notificationId,
	} );
}

/**
 * Renders the "Terms apply." link used across ad-credit notification descriptions.
 *
 * @param {string} linkId An identifier for this link, sent as part of track event properties.
 */
const TermsApplyLink = ( { linkId, children } ) => {
	return (
		<AppDocumentationLink
			context={ CONTEXT_MARKETING_OVERVIEW }
			linkId={ linkId }
			href={ TERMS_URL }
		>
			{ children }
		</AppDocumentationLink>
	);
};

/**
 * @typedef {Object} NotificationConfig
 * @property {string} title Notification headline.
 * @property {string} description Notification body text.
 * @property {Array<Object>} actions Array of AppButton prop objects for CTA buttons.
 * @property {boolean} [isReady] Whether the config data has finished resolving. Omitted for static configs (renders immediately); set to a resolution flag for dynamic configs.
 */

const setupAdsUrl = getSetupAdsUrl();
const dashboardUrl = getDashboardUrl();
const settingsUrl = getSettingsUrl();
const wcTrackingSettingsUrl = getWCTrackingSettingsUrl();
const onboardingUrl = getOnboardingUrl();
const wcCouponsUrl = getWCCouponsUrl();

/**
 * Static notification configs — created once at module level, never re-created on render.
 *
 * @type {Object.<string, NotificationConfig>}
 */
const STATIC_MAP = {
	'abandoned-onboarding': {
		title: __( 'Finish your Google Ads setup', 'google-listings-and-ads' ),
		description: createInterpolateElement(
			__(
				'Your Google Ads integration setup was interrupted. Complete the remaining configuration steps to ensure your store data is properly synced. Get $500 USD or more in Google ad credit. Offer for new advertisers only. <link>Terms apply.</link>',
				'google-listings-and-ads'
			),
			{ link: <TermsApplyLink linkId="abandoned-onboarding" /> }
		),
		actions: [
			{
				id: 'continue-setup',
				href: onboardingUrl,
				children: __( 'Continue Setup', 'google-listings-and-ads' ),
			},
		],
	},
	'paid-orders': {
		title: __(
			'Drive more sales with Google Ads',
			'google-listings-and-ads'
		),
		description: createInterpolateElement(
			__(
				"Congrats on your first 10 sales – now let's find your next customer. Reach high-intent shoppers across Google. Get $500 USD or more in Google ad credit. Offer for new advertisers only. <link>Terms apply.</link>",
				'google-listings-and-ads'
			),
			{ link: <TermsApplyLink linkId="paid-orders" /> }
		),
		actions: [
			{
				id: 'setup-ads-campaign',
				href: setupAdsUrl,
				children: __(
					'Set up Google Ads campaign',
					'google-listings-and-ads'
				),
			},
		],
	},
	'ready-but-no-sales': {
		title: __(
			'Get more sales with Google Ads',
			'google-listings-and-ads'
		),
		description: createInterpolateElement(
			__(
				"Reach the right shoppers when they're searching for products like yours across Google (including Search, Shopping, YouTube, and more) in just a few easy steps! Get $500 USD or more in Google ad credit. Offer for new advertisers only. <link>Terms apply.</link>",
				'google-listings-and-ads'
			),
			{ link: <TermsApplyLink linkId="ready-but-no-sales" /> }
		),
		actions: [
			{
				id: 'get-started',
				href: setupAdsUrl,
				children: __( 'Get started', 'google-listings-and-ads' ),
			},
		],
	},
	'product-issues': {
		title: __( 'Resolve product sync errors', 'google-listings-and-ads' ),
		description: __(
			'Some of your products are not visible on Google due to configuration issues. Review and fix these errors to ensure your full inventory is operational and can start appearing across Google.',
			'google-listings-and-ads'
		),
		actions: [
			{
				id: 'view-product-issues',
				href: getProductFeedUrl(),
				children: __(
					'View Product Issues',
					'google-listings-and-ads'
				),
			},
		],
	},
	'campaign-no-sales': {
		title: __( 'Drive traffic from Google Ads', 'google-listings-and-ads' ),
		description: __(
			"Your campaign is active, but hasn't generated sales yet. Review your account recommendations in Google Ads to find specific ways to improve your performance.",
			'google-listings-and-ads'
		),
		actions: [
			{
				id: 'view-recommendations',
				href: 'https://ads.google.com/aw/recommendations',
				target: '_blank',
				rel: 'noopener noreferrer',
				children: __(
					'View recommendations',
					'google-listings-and-ads'
				),
			},
		],
	},
	'enhanced-conversions-off': {
		title: __(
			'Enable Enhanced Conversions for accurate reporting',
			'google-listings-and-ads'
		),
		description: __(
			'To improve the accuracy of your sales data and attribution, you must enable Enhanced Conversions in your account settings.',
			'google-listings-and-ads'
		),
		actions: [
			{
				id: 'enable-feature',
				href: settingsUrl,
				children: __( 'Enable Feature', 'google-listings-and-ads' ),
			},
		],
	},
	'recommendations-available': {
		title: __(
			'Improve your Google Ads campaigns',
			'google-listings-and-ads'
		),
		description: __(
			'You have personalized recommendations to improve your Google Ads campaigns.',
			'google-listings-and-ads'
		),
		actions: [
			{
				id: 'see-recommendations',
				href: dashboardUrl,
				children: __(
					'See recommendations here',
					'google-listings-and-ads'
				),
			},
		],
	},
	'tracking-off': {
		title: __(
			'You are missing out on personalized recommendations',
			'google-listings-and-ads'
		),
		description: __(
			'Turn on tracking to receive relevant recommendations on how you can grow your business.',
			'google-listings-and-ads'
		),
		actions: [
			{
				id: 'turn-on-tracking',
				href: wcTrackingSettingsUrl,
				children: __( 'Turn on tracking', 'google-listings-and-ads' ),
			},
		],
	},
	'collect-google-customer-reviews': {
		title: __(
			'Collect Google Reviews after purchase',
			'google-listings-and-ads'
		),
		description: __(
			'Google Reviews provide free social proof, increased organic visibility, and a boost to advertising performance.',
			'google-listings-and-ads'
		),
		actions: [
			{
				id: 'enable-reviews-collection',
				href: settingsUrl,
				children: __(
					'Enable reviews collection',
					'google-listings-and-ads'
				),
			},
		],
	},
	'google-customer-reviews-badge-widget': {
		title: __( 'Add your store rating widget', 'google-listings-and-ads' ),
		description: __(
			'Show Google-verified ratings and reviews on your site and boost shopper trust and conversions.',
			'google-listings-and-ads'
		),
		actions: [
			{
				id: 'add-widget',
				href: settingsUrl,
				children: __( 'Add widget', 'google-listings-and-ads' ),
			},
		],
	},
};

/**
 * Builds the `onClick` handler for a CTA action that must save a settings
 * field before navigating to its `href`, instead of just navigating.
 *
 * @param {string} notificationId ID of the notification the action belongs to.
 * @param {string} settingKey Settings field to set to `true` before navigating.
 * @param {Object} settings Current settings values, spread into the save call.
 * @param {Function} saveSettings Action to persist the updated settings.
 * @return {Function} `onClick( event, action )` handler for the action.
 */
const createSaveSettingOnClick =
	( notificationId, settingKey, settings, saveSettings ) =>
	async ( event, action ) => {
		try {
			await saveSettings( { ...settings, [ settingKey ]: true } );
			window.location.assign(
				withReferrer( action.href, notificationId )
			);
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error updating the setting. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

/**
 * Returns a map of notification configs keyed by notification ID.
 *
 * Static entries are defined at module level and never re-created on render.
 * Dynamic entries that depend on hooks are merged in via useMemo with their dependencies.
 *
 * @return {Object.<string, NotificationConfig>} Map of notification ID to its display config.
 */
const useNotificationsSystemMap = () => {
	const { hasGoogleMCConnection, hasFinishedResolution } =
		useGoogleMCAccount();
	const { settings, saveSettings } = useSettings();

	const settingCtaMap = useMemo( () => {
		const withSettingOnClick = ( notificationId, settingKey ) => {
			const config = STATIC_MAP[ notificationId ];

			return {
				...config,
				actions: [
					{
						...config.actions[ 0 ],
						onClick: createSaveSettingOnClick(
							notificationId,
							settingKey,
							settings,
							saveSettings
						),
					},
				],
			};
		};

		return {
			'collect-google-customer-reviews': withSettingOnClick(
				'collect-google-customer-reviews',
				'collect_reviews_after_purchase'
			),
			'google-customer-reviews-badge-widget': withSettingOnClick(
				'google-customer-reviews-badge-widget',
				'badge_widget_enabled'
			),
		};
	}, [ settings, saveSettings ] );

	const dynamicMap = useMemo( () => {
		return {
			'skipped-campaign-creation': {
				isReady: hasFinishedResolution,
				title: __(
					'Finish setting up Google Ads',
					'google-listings-and-ads'
				),
				description: ! hasGoogleMCConnection
					? createInterpolateElement(
							__(
								'Your campaign is not live. Finish setup now to begin showing your business services across Google (Including Search, Shopping, YouTube, and more). Get $500 USD or more in Google ad credit. Offer for new advertisers only. <link>Terms apply.</link>',
								'google-listings-and-ads'
							),
							{
								link: (
									<TermsApplyLink linkId="skipped-campaign-creation-no-mc" />
								),
							}
					  )
					: createInterpolateElement(
							__(
								'Your campaign is not live. Finish setup now to begin showing your products across Google (Including Search, Shopping, YouTube, and more). Get $500 USD or more in Google ad credit. Offer for new advertisers only. <link>Terms apply.</link>',
								'google-listings-and-ads'
							),
							{
								link: (
									<TermsApplyLink linkId="skipped-campaign-creation" />
								),
							}
					  ),
				actions: [
					{
						id: 'complete-campaign-setup',
						href: setupAdsUrl,
						children: __(
							'Complete Campaign Setup',
							'google-listings-and-ads'
						),
					},
				],
			},
			'not-onboarded': {
				isReady: hasFinishedResolution,
				title: __(
					'Finish your Google for WooCommerce connection',
					'google-listings-and-ads'
				),
				description: ! hasGoogleMCConnection
					? __(
							'The plugin is active but not yet connected to a Google account. Link your account and start your first Google Ads campaign.',
							'google-listings-and-ads'
					  )
					: __(
							'The plugin is active but not yet connected to a Google account. Link your account to sync your product data and start showing your inventory to shoppers.',
							'google-listings-and-ads'
					  ),
				actions: [
					{
						id: 'setup-here',
						href: onboardingUrl,
						children: __( 'Setup here', 'google-listings-and-ads' ),
					},
				],
			},
			'paused-campaign': {
				isReady: hasFinishedResolution,
				title: __(
					'Your Google Ads campaign is paused',
					'google-listings-and-ads'
				),
				description: ! hasGoogleMCConnection
					? __(
							'Your ads are not currently running.',
							'google-listings-and-ads'
					  )
					: __(
							'Your products are not currently appearing to shoppers.',
							'google-listings-and-ads'
					  ),
				actions: [
					{
						id: 'resume-campaign',
						href: dashboardUrl,
						children: __(
							'Resume your campaign',
							'google-listings-and-ads'
						),
					},
				],
			},
			'sales-not-growing': {
				isReady: hasFinishedResolution,
				title: ! hasGoogleMCConnection
					? __(
							'Increase your site traffic',
							'google-listings-and-ads'
					  )
					: __(
							"You're not growing sales",
							'google-listings-and-ads'
					  ),
				description: ! hasGoogleMCConnection
					? createInterpolateElement(
							__(
								'Generate more customers with Google Ads. Get $500 USD or more in Google ad credit. Offer for new advertisers only. <link>Terms apply.</link>',
								'google-listings-and-ads'
							),
							{
								link: (
									<TermsApplyLink linkId="sales-not-growing-no-mc" />
								),
							}
					  )
					: createInterpolateElement(
							__(
								'Generate more sales with Google Ads. Get $500 USD or more in Google ad credit. Offer for new advertisers only. <link>Terms apply.</link>',
								'google-listings-and-ads'
							),
							{
								link: (
									<TermsApplyLink linkId="sales-not-growing" />
								),
							}
					  ),
				actions: [
					{
						id: 'launch-campaign',
						href: setupAdsUrl,
						children: __(
							'Launch a campaign today',
							'google-listings-and-ads'
						),
					},
				],
			},
			'coupons-not-synced': {
				isReady: hasFinishedResolution,
				title: __(
					'Promote your coupons on Google',
					'google-listings-and-ads'
				),
				description: ! hasGoogleMCConnection
					? __(
							'Your WooCommerce coupons are not currently synced to your Google feed. Sync them today to show these offers to customers searching for your products.',
							'google-listings-and-ads'
					  )
					: __(
							'Your WooCommerce coupons are not currently synced to your Google feed. Sync them today to show these offers to shoppers searching for your products.',
							'google-listings-and-ads'
					  ),
				actions: [
					{
						id: 'review-coupon-settings',
						href: wcCouponsUrl,
						children: __(
							'Review coupon settings',
							'google-listings-and-ads'
						),
					},
				],
			},
		};
	}, [ hasFinishedResolution, hasGoogleMCConnection ] );

	return useMemo(
		() => ( { ...STATIC_MAP, ...settingCtaMap, ...dynamicMap } ),
		[ settingCtaMap, dynamicMap ]
	);
};

export default useNotificationsSystemMap;
