/**
 * External dependencies
 */
import { getHistory } from '@woocommerce/navigation';
import {
	createInterpolateElement,
	useEffect,
	useCallback,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import Guide from '.~/external-components/wordpress/guide';
import GuidePageContent, {
	ContentLink,
} from '.~/components/guide-page-content';
import AppButton from '.~/components/app-button';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import {
	glaData,
	GUIDE_NAMES,
	LOCAL_STORAGE_KEYS,
	GOOGLE_ADS_ACCOUNT_STATUS,
	CAMPAIGN_TYPE_PMAX,
} from '.~/constants';
import localStorage from '.~/utils/localStorage';
import { getProductFeedUrl } from '.~/utils/urls';
import wooLogoURL from './woocommerce-logo.svg';
import googleLogoURL from '.~/images/google-logo.svg';
import { recordGlaEvent } from '.~/utils/tracks';
import EnhancedConversion from './EnhancedConversion';
import EnhancedConversionFooter from './EnhancedConversionFooter';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import './index.scss';

const EVENT_NAME = 'gla_modal_closed';
const SUCCESS_SCREEN = 'success-screen';
const GOOGLE_ADS_CREDITS_SCREEN = 'google-ads-credits-screen';
const ENHANCED_CONVERSION_TRACKING_SCREEN =
	'enhanced-conversion-tracking-screen';

const image = (
	<div className="gla-submission-success-guide__logo-block">
		<div className="gla-submission-success-guide__logo-item">
			<img
				src={ wooLogoURL }
				alt={ __( 'WooCommerce Logo', 'google-listings-and-ads' ) }
				width="145"
				height="31"
			/>
		</div>
		<div className="gla-submission-success-guide__logo-separator-line" />
		<div className="gla-submission-success-guide__logo-item">
			<img
				src={ googleLogoURL }
				alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
				width="106"
				height="36"
			/>
		</div>
	</div>
);

const handleGuideFinish = ( e ) => {
	getHistory().replace( getProductFeedUrl() );

	// Since there is no built-in way to distinguish the modal/guide is closed by what action,
	// here is a workaround by identifying the close button's data-aciton attribute.
	const target = e.currentTarget || e.target;
	const action = target.dataset.action || 'dismiss';
	recordGlaEvent( EVENT_NAME, {
		context: GUIDE_NAMES.SUBMISSION_SUCCESS,
		action,
	} );
};

const PAGES = {
	[ SUCCESS_SCREEN ]: {
		image,
		content: (
			<GuidePageContent
				title={ __(
					'You’ve successfully set up Google Listings & Ads! 🎉',
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
		footer: (
			<AppButton
				isPrimary
				data-action="view-product-feed"
				onClick={ handleGuideFinish }
			>
				{ __( 'View product feed', 'google-listings-and-ads' ) }
			</AppButton>
		),
	},
};

/**
 * Modal window to greet the user at Product Feed, after successful completion of onboarding.
 *
 * Show this guide modal by visiting the path with a specific query `guide=submission-success`.
 * For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed&guide=submission-success`.
 *
 * @fires gla_modal_closed with `action: 'create-paid-campaign' | 'maybe-later' | 'view-product-feed' | 'dismiss'`
 * @fires gla_modal_open with `context: GUIDE_NAMES.SUBMISSION_SUCCESS`
 */
const SubmissionSuccessGuide = () => {
	const { data: campaigns, loaded: adsCampaignsLoaded } = useAdsCampaigns();
	const { googleAdsAccount, hasFinishedResolution: googleAdsAccountLoaded } =
		useGoogleAdsAccount();

	const pmaxCampaigns = campaigns?.filter(
		( { type } ) => type === CAMPAIGN_TYPE_PMAX
	);

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

	// @todo: Review whether we need that function since we have moved the buttons to be per page now.
	const renderFinish = useCallback( () => null, [] );

	const showEnhancedConversionTrackingScreen =
		pmaxCampaigns?.length &&
		googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED &&
		googleAdsAccountLoaded &&
		adsCampaignsLoaded;

	const showGoogleAdsCreditsScreen =
		! pmaxCampaigns?.length && googleAdsAccountLoaded && adsCampaignsLoaded;

	if ( showEnhancedConversionTrackingScreen ) {
		delete PAGES[ GOOGLE_ADS_CREDITS_SCREEN ];

		PAGES[ ENHANCED_CONVERSION_TRACKING_SCREEN ] = {
			image,
			content: <EnhancedConversion />,
			footer: (
				<EnhancedConversionFooter
					handleGuideFinish={ handleGuideFinish }
				/>
			),
		};
	} else if ( showGoogleAdsCreditsScreen ) {
		delete PAGES[ ENHANCED_CONVERSION_TRACKING_SCREEN ];

		// There are no campaigns, add Google Ads credit screen
		PAGES[ GOOGLE_ADS_CREDITS_SCREEN ] = {
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
			footer: (
				<>
					<div className="gla-submission-success-guide__space_holder" />

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
						{ __(
							'Create paid campaign',
							'google-listings-and-ads'
						) }
					</AddPaidCampaignButton>

					<AppButton
						isSecondary
						data-action="maybe-later"
						onClick={ handleGuideFinish }
					>
						{ __( 'Maybe later', 'google-listings-and-ads' ) }
					</AppButton>
				</>
			),
		};
	}

	const pages = Object.keys( PAGES ).map( ( screen ) => PAGES[ screen ] );

	return (
		<Guide
			className="gla-submission-success-guide"
			backButtonText={ __( 'Back', 'google-listings-and-ads' ) }
			pages={ pages }
			renderFinish={ renderFinish }
			onFinish={ handleGuideFinish }
		/>
	);
};

export default SubmissionSuccessGuide;
