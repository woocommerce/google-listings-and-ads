/**
 * External dependencies
 */
import {
	createInterpolateElement,
	useCallback,
	useMemo,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useSettings from '~/hooks/useSettings';
import AppDocumentationLink from '~/components/app-documentation-link';
import { handleApiError } from '~/utils/handleError';
import { CONTEXT_MARKETING_OVERVIEW } from '~/utils/tracks';
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
	const { saveSettings } = useSettings();
	const [ processingNotificationId, setProcessingNotificationId ] =
		useState( null );

	/**
	 * Saves a notification-related setting and tracks the notification ID being processed.
	 *
	 * @param {string} id Notification ID, used to track/disable its own CTA while saving.
	 * @param {string} settingKey The key of the setting to save.
	 * @throws Re-throws after surfacing the error, so callers can decide whether to navigate.
	 */
	const saveNotificationSetting = useCallback(
		async ( id, settingKey ) => {
			setProcessingNotificationId( id );

			try {
				await saveSettings( { [ settingKey ]: true } );
			} catch ( error ) {
				handleApiError(
					error,
					__(
						'There was an error updating the setting. Please try again.',
						'google-listings-and-ads'
					)
				);
				throw error;
			} finally {
				setProcessingNotificationId( null );
			}
		},
		[ saveSettings ]
	);

	/**
	 * Handles the click of a notification CTA that saves a setting and then navigates.
	 * Once saved, the notification is dismissed (calls the passed-through `onDismiss`
	 * prop — a temporary, local removal from the panel; it does not persist
	 * server-side) since it no longer needs to be acted on.
	 *
	 * @param {string} id Notification ID being saved for.
	 * @param {string} settingKey The key of the setting to save.
	 * @param {Event} event The click event from the CTA button.
	 * @param {Function} dismissNotification Dismisses this notification (calls `onDismiss`).
	 */
	const handleNotificationSettingClick = useCallback(
		async ( id, settingKey, event, dismissNotification ) => {
			event.preventDefault();

			// Read the href now — React nulls out `event.currentTarget` once
			// this synchronous dispatch phase ends, so it's gone by the time
			// the `await` below resolves. Can't move this closer to its use
			// at the end of the function for that reason.
			// eslint-disable-next-line @wordpress/no-unused-vars-before-return
			const href = event.currentTarget.getAttribute( 'href' );

			try {
				await saveNotificationSetting( id, settingKey );
			} catch {
				// Error already surfaced by saveNotificationSetting; don't
				// dismiss or navigate.
				return;
			}

			dismissNotification( id );
			getHistory().push( href );
		},
		[ saveNotificationSetting ]
	);

	/**
	 * Handles the click of the "Enable reviews collection" CTA in the
	 * "Collect Google Reviews after purchase" notification.
	 *
	 * @param {Event} event The click event from the CTA button.
	 * @param {Function} dismissNotification Dismisses the notification after saving the setting.
	 */
	const handleCollectGoogleCustomerReviewsClick = useCallback(
		( event, dismissNotification ) => {
			return handleNotificationSettingClick(
				'google-customer-reviews-collect-reviews',
				'collect_reviews_after_purchase',
				event,
				dismissNotification
			);
		},
		[ handleNotificationSettingClick ]
	);

	/**
	 * Handles the click of the "Add widget" CTA in the "Add your store rating
	 * widget" notification.
	 *
	 * @param {Event} event The click event from the CTA button.
	 * @param {Function} dismissNotification Dismisses the notification after saving the setting.
	 */
	const handleGoogleCustomerReviewsBadgeWidgetClick = useCallback(
		( event, dismissNotification ) => {
			return handleNotificationSettingClick(
				'google-customer-reviews-badge-widget',
				'badge_widget_enabled',
				event,
				dismissNotification
			);
		},
		[ handleNotificationSettingClick ]
	);

	const dynamicMap = useMemo( () => {
		return {
			'google-customer-reviews-collect-reviews': {
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
						onClick: handleCollectGoogleCustomerReviewsClick,
						disabled:
							processingNotificationId ===
							'google-customer-reviews-collect-reviews',
						children: __(
							'Enable reviews collection',
							'google-listings-and-ads'
						),
					},
				],
			},
			'google-customer-reviews-badge-widget': {
				title: __(
					'Add your store rating widget',
					'google-listings-and-ads'
				),
				description: __(
					'Show Google-verified ratings and reviews on your site and boost shopper trust and conversions.',
					'google-listings-and-ads'
				),
				actions: [
					{
						id: 'add-widget',
						href: settingsUrl,
						onClick: handleGoogleCustomerReviewsBadgeWidgetClick,
						disabled:
							processingNotificationId ===
							'google-customer-reviews-badge-widget',
						children: __( 'Add widget', 'google-listings-and-ads' ),
					},
				],
			},
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
	}, [
		hasFinishedResolution,
		hasGoogleMCConnection,
		processingNotificationId,
		handleCollectGoogleCustomerReviewsClick,
		handleGoogleCustomerReviewsBadgeWidgetClick,
	] );

	return useMemo(
		() => ( { ...STATIC_MAP, ...dynamicMap } ),
		[ dynamicMap ]
	);
};

export default useNotificationsSystemMap;
