/**
 * External dependencies
 */
import { Flex, FlexBlock } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { CHANNEL_VISIBILITY_CONTEXT } from './constants';
import GetStartedCTA from './get-started-cta';

/**
 * Google Ads Promo "Dismiss" button is clicked.
 *
 * @event gla_google_ads_promo_dismiss_click
 * @property {string} context Context of the Google Ads Promo.
 */

/**
 * Google Ads Promo CTA component.
 *
 * @fires gla_google_ads_promo_dismiss_click with `{ context: channel-visibility-meta-box }`.
 * @param {Function} onDismiss The function to call when the dismiss button is clicked.
 *
 * @return {JSX.Element} The Google Ads Promo CTA component.
 */
const PromoCTA = ( { onDismiss } ) => {
	return (
		<Flex align="flex-start" gap={ 3 }>
			<FlexBlock>
				<GetStartedCTA />
			</FlexBlock>

			<FlexBlock>
				<AppButton
					eventName="gla_google_ads_promo_dismiss_click"
					eventProps={ {
						context: CHANNEL_VISIBILITY_CONTEXT,
					} }
					onClick={ onDismiss }
					isTertiary
				>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexBlock>
		</Flex>
	);
};

export default PromoCTA;
