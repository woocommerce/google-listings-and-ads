/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import { getCreateCampaignUrl, getGetStartedUrl } from '~/utils/urls';
import AppButton from '~/components/app-button';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';
import googleLogoURL from '~/images/logo/google-g-logo.svg';
import './google-ads-promo.scss';

/**
 * Google Ads Promo component is shown.
 *
 * @event gla_google_ads_promo_shown
 * @property {string} context Context of the Google Ads Promo.
 */

/**
 * Google Ads Promo "Get started" button is clicked.
 *
 * @event gla_google_ads_promo_get_started_click
 * @property {string} context Context of the Google Ads Promo.
 * @property {string} href URL of the "Get started" button.
 */

/**
 * Google Ads Promo "Create campaign" button is clicked.
 *
 * @event gla_google_ads_promo_create_campaign_click
 * @property {string} context Context of the Google Ads Promo.
 * @property {string} href URL of the "Create campaign" button.
 */

/**
 * Google Ads Promo component.
 *
 * @fires gla_google_ads_promo_shown with `{ context: 'order-attribution-meta-box' }`.
 * @fires gla_google_ads_promo_get_started_click with `{ context: 'order-attribution-meta-box', href: 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart' }`.
 * @fires gla_google_ads_promo_create_campaign_click with `{ context: 'order-attribution-meta-box', href: 'admin.php?page=wc-admin&subpath=%2Fcampaigns%2Fcreate&path=%2Fgoogle%2Fdashboard' }`.
 *
 * @return {JSX.Element|null} The Google Ads Promo component or null.
 */
const GoogleAdsPromo = () => {
	const context = 'order-attribution-meta-box';
	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: hasResolvedGoogleAdsAccount,
	} = useGoogleAdsAccount();
	const { hasAdSpend, hasFinishedResolution: hasResolvedRecentAdSpend } =
		useHasRecentAdSpend();
	const hasTrackedRef = useRef( false );

	const isResolved = hasResolvedGoogleAdsAccount && hasResolvedRecentAdSpend;
	const shouldShowPromo = isResolved && ! hasAdSpend;

	useEffect( () => {
		// Only fire if all conditions for rendering are met and not already tracked
		if ( ! hasTrackedRef.current && shouldShowPromo ) {
			recordGlaEvent( 'gla_google_ads_promo_shown', {
				context,
			} );
			hasTrackedRef.current = true;
		}
	}, [ shouldShowPromo ] );

	if ( ! shouldShowPromo ) {
		return null;
	}

	let content;
	if ( hasGoogleAdsConnection ) {
		const campaignUrl = getCreateCampaignUrl();
		content = {
			title: __(
				'Get more sales with Google Ads',
				'google-listings-and-ads'
			),
			description: __(
				'Launch a Google Ads campaign and get your products discovered by high-intent shoppers across Google',
				'google-listings-and-ads'
			),
			cta: (
				<AppButton
					href={ campaignUrl }
					eventName="gla_google_ads_promo_create_campaign_click"
					eventProps={ {
						href: campaignUrl,
						context,
					} }
					isSecondary
				>
					{ __( 'Create campaign', 'google-listings-and-ads' ) }
				</AppButton>
			),
		};
	} else {
		const getStartedUrl = getGetStartedUrl();
		content = {
			title: __(
				'Get your products on Google',
				'google-listings-and-ads'
			),
			description: __(
				'Sync your products to reach customers when they’re searching for products like yours across Google',
				'google-listings-and-ads'
			),
			cta: (
				<AppButton
					href={ getStartedUrl }
					eventName="gla_google_ads_promo_get_started_click"
					eventProps={ {
						href: getStartedUrl,
						context,
					} }
					isSecondary
				>
					{ __( 'Get started', 'google-listings-and-ads' ) }
				</AppButton>
			),
		};
	}

	const { title, description, cta } = content;

	return (
		<Flex className="gla-google-ads-promo" direction="column" gap={ 3 }>
			<FlexBlock>
				<Flex gap={ 2 } align="center">
					<FlexItem>
						<img
							className="gla-google-ads-promo__logo"
							src={ googleLogoURL }
							alt={ __(
								'Google Logo',
								'google-listings-and-ads'
							) }
							width={ 24 }
							height={ 24 }
						/>
					</FlexItem>
					<FlexBlock>
						<h3 className="gla-google-ads-promo__title">
							{ title }
						</h3>
					</FlexBlock>
				</Flex>
			</FlexBlock>
			<FlexBlock>
				<p>{ description }</p>
			</FlexBlock>
			<FlexBlock>{ cta }</FlexBlock>
		</Flex>
	);
};

export default GoogleAdsPromo;
