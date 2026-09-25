/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Notice } from '@wordpress/components';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import { NOTICE_DISMISSED_KEY, CTA_URL } from './constants';
import AppButton from '~/components/app-button';
import usePreference from '~/hooks/usePreference';
import useAdsSettings from '~/hooks/useAdsSettings';
import './index.scss';

/**
 * Triggered when the "Apply in Google Ads" button is clicked in the unclaimed incentive notice.
 *
 * @event gla_unclaimed_incentive_notice_apply_offer_click
 */

/**
 * Notice component to inform users about unclaimed ads incentives
 * and provide a link to apply the offer in Google Ads.
 * The notice can be dismissed by the user, and the dismissal state
 * is stored in preferences to prevent showing the notice again.
 *
 * @fires gla_unclaimed_incentive_notice_apply_offer_click when the "Apply in Google Ads" button is clicked.
 */
const UnclaimedIncentiveNotice = () => {
	const { adsSettings } = useAdsSettings();
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( NOTICE_DISMISSED_KEY );

	if ( ! adsSettings?.ads_has_unclaimed_incentive || isDismissed ) {
		return null;
	}

	const handleDismiss = () => {
		set( PREFERENCES_STORE_NAMESPACE, NOTICE_DISMISSED_KEY, true );
	};

	return (
		<Notice
			status="warning"
			isDismissible={ true }
			onRemove={ handleDismiss }
			className="gla-unclaimed-incentive-notice"
		>
			<p>
				{ __(
					"Your ads credit offer couldn't be applied. You can try again, or apply it directly in Google Ads. You have 14 days from your first ad impression to select an offer.",
					'google-listings-and-ads'
				) }
			</p>

			<AppButton
				href={ CTA_URL }
				target="_blank"
				variant="secondary"
				eventName="gla_unclaimed_incentive_notice_apply_offer_click"
				eventProps={ {
					url: CTA_URL,
				} }
				onClick={ handleDismiss }
			>
				{ __( 'Apply in Google Ads', 'google-listings-and-ads' ) }
			</AppButton>
		</Notice>
	);
};

export default UnclaimedIncentiveNotice;
