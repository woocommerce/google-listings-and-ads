/**
 * External dependencies
 */
import { getHistory } from '@woocommerce/navigation';
import { createInterpolateElement, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Guide from '~/components/external/wordpress/guide';
import GuidePageContent, { ContentLink } from '~/components/guide-page-content';
import AppButton from '~/components/app-button';
import AddPaidCampaignButton from '~/components/paid-ads/add-paid-campaign-button';
import { glaData, GUIDE_NAMES, LOCAL_STORAGE_KEYS } from '~/constants';
import localStorage from '~/utils/localStorage';
import {
	getDashboardUrl,
	getProductFeedUrl,
	getSettingsUrl,
} from '~/utils/urls';
import wooLogoURL from '~/images/logo/woocommerce-logo.svg';
import googleLogoURL from '~/images/logo/google-logo.svg';
import { recordGlaEvent } from '~/utils/tracks';
import './index.scss';

const EVENT_NAME = 'gla_modal_closed';

const handleGuideFinish = ( e ) => {
	// If there is no connected MC account, redirect to dashboard, otherwise to product feed.
	const url = glaData.mcSetupComplete
		? getProductFeedUrl()
		: getDashboardUrl();
	getHistory().replace( url );

	// Since there is no built-in way to distinguish the modal/guide is closed by what action,
	// here is a workaround by identifying the close button's data-action attribute.
	let action = 'dismiss';

	if ( e ) {
		const target = e.currentTarget || e.target;
		action = target.dataset.action || action;
	}
	recordGlaEvent( EVENT_NAME, {
		context: GUIDE_NAMES.SUBMISSION_SUCCESS,
		action,
	} );
};

const handleSetupEnhancedConversionsOnClick = () => {
	handleGuideFinish();
	getHistory().push( getSettingsUrl() );
};

const image = (
	<div className="gla-submission-success-guide__logo-block">
		<div className="gla-submission-success-guide__logo-item gla-submission-success-guide__logo-item--woocommerce">
			<img
				src={ wooLogoURL }
				alt={ __( 'WooCommerce Logo', 'google-listings-and-ads' ) }
				width="187.5"
			/>
		</div>
		<div className="gla-submission-success-guide__logo-separator-line" />
		<div className="gla-submission-success-guide__logo-item">
			<img
				src={ googleLogoURL }
				alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
				width="85"
			/>
		</div>
	</div>
);

const pages = [
	{
		image,
		content: (
			<GuidePageContent
				title={ __(
					'You’ve successfully set up Google for WooCommerce! 🎉',
					'google-listings-and-ads'
				) }
			>
				<p>
					{ __(
						'Your products are being synced and reviewed. Google reviews product listings in 3-5 days.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ glaData.adsSetupComplete
						? __(
								'No ads will launch yet and you won’t be charged until Google approves your listings. Updates are available in your WooCommerce dashboard.',
								'google-listings-and-ads'
						  )
						: createInterpolateElement(
								__(
									'<productFeedLink>Manage and edit your product feed in WooCommerce.</productFeedLink> We will also notify you of any product feed issues to ensure your products get approved and perform well on Google.',
									'google-listings-and-ads'
								),
								{
									productFeedLink: (
										<ContentLink
											href={ getProductFeedUrl() }
											context="product-feed"
										/>
									),
								}
						  ) }
				</p>
			</GuidePageContent>
		),
		action: glaData.adsSetupComplete ? (
			<AppButton
				isPrimary
				data-action="view-product-feed"
				onClick={ handleGuideFinish }
			>
				{ __( 'View product feed', 'google-listings-and-ads' ) }
			</AppButton>
		) : undefined,
	},
	{
		image,
		content: (
			<GuidePageContent
				title={ __(
					'Improve conversion tracking accuracy to improve campaign performance',
					'google-listings-and-ads'
				) }
			>
				<p>
					{ __(
						'Set up Enhanced Conversions, a feature designed to improve your measurement accuracy by collecting privacy-conscious data without the need for third-party cookies.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<link>Learn more</link> about Enhanced Conversions.',
							'google-listings-and-ads'
						),
						{
							link: (
								<ContentLink
									href="https://support.google.com/google-ads/answer/9888656"
									context="enhanced-conversions"
								/>
							),
						}
					) }
				</p>
			</GuidePageContent>
		),
		actions: (
			<AppButton
				isPrimary
				data-action="view-enhanced-conversions-settings"
				eventName={ EVENT_NAME }
				eventProps={ {
					context: GUIDE_NAMES.SUBMISSION_SUCCESS,
					action: 'view-enhanced-conversions-settings',
				} }
				onClick={ handleSetupEnhancedConversionsOnClick }
			>
				{ __(
					'Set up Enhanced Conversions',
					'google-listings-and-ads'
				) }
			</AppButton>
		),
	},
	{
		image,
		content: (
			<GuidePageContent
				title={ __(
					'Spend $500 to get $500 in Google Ads credits',
					'google-listings-and-ads'
				) }
			>
				<p>
					{ __(
						'New to Google Ads? Get $500 in ad credit when you spend $500 within your first 60 days* You can edit or cancel your campaign at any time.',
						'google-listings-and-ads'
					) }
				</p>
				<cite>
					{ createInterpolateElement(
						__(
							'*Full terms and conditions <link>here</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<ContentLink
									href="https://www.google.com/ads/coupons/terms/"
									context="terms-of-ads-coupons"
								/>
							),
						}
					) }
				</cite>
			</GuidePageContent>
		),
		actions: (
			<>
				<AppButton
					isSecondary
					data-action="maybe-later"
					onClick={ handleGuideFinish }
				>
					{ __( 'Maybe later', 'google-listings-and-ads' ) }
				</AppButton>
				<AddPaidCampaignButton
					isPrimary
					isSecondary={ false }
					isSmall={ false }
					eventName={ EVENT_NAME }
					eventProps={ {
						context: GUIDE_NAMES.SUBMISSION_SUCCESS,
						action: 'create-paid-campaign',
					} }
				>
					{ __( 'Create campaign', 'google-listings-and-ads' ) }
				</AddPaidCampaignButton>
			</>
		),
	},
];

if ( glaData.adsSetupComplete ) {
	pages.pop();
}

/**
 * Modal window to greet the user at Product Feed, after successful completion of onboarding.
 *
 * Show this guide modal by visiting the path with a specific query `guide=submission-success`.
 * For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed&guide=submission-success`.
 *
 * @fires gla_modal_closed with `action: 'create-paid-campaign' | 'maybe-later' | 'view-product-feed' | 'dismiss' | 'view-enhanced-conversions-settings'`
 * @fires gla_modal_open with `context: GUIDE_NAMES.SUBMISSION_SUCCESS`
 */
const SubmissionSuccessGuide = () => {
	useEffect( () => {
		recordGlaEvent( 'gla_modal_open', {
			context: GUIDE_NAMES.SUBMISSION_SUCCESS,
		} );

		// Set a flag in local storage to indicate the CES prompt can be shown
		// when the user enters product feed for the first time after setting up.
		localStorage.set(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN,
			true
		);
	}, [] );

	return (
		<Guide
			className="gla-submission-success-guide"
			backButtonText={ __( 'Back', 'google-listings-and-ads' ) }
			pages={ pages }
			onFinish={ handleGuideFinish }
		/>
	);
};

export default SubmissionSuccessGuide;
