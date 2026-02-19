/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE, glaData } from '~/constants';
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';
import GoogleAdsPromoCTA from './google-ads-promo-cta';
import GoogleAdsPromoSetupCompleted from './google-ads-promo-setup-completed';
import GetStartedCTA from './get-started-cta';
import usePreference from '~/hooks/usePreference';
import './google-ads-promo.scss';

const { adsSetupComplete } = glaData;

const PREFERENCE_BANNER_KEY = 'gla_google_ads_promo_dismissed';

/**
 * Google Ads Promo component.
 *
 * @return {JSX.Element|null} The Google Ads Promo component or null.
 */
const GoogleAdsPromo = () => {
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( PREFERENCE_BANNER_KEY );

	const handleDismiss = () => {
		set( PREFERENCES_STORE_NAMESPACE, PREFERENCE_BANNER_KEY, true );
	};

	if ( adsSetupComplete ) {
		return <GoogleAdsPromoSetupCompleted />;
	}

	return (
		<Flex
			className="gla-channel-visibility-google-ads-promo"
			direction="column"
			gap={ 4 }
		>
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="flex-start">
					<FlexItem>
						<img
							src={ googleLogoURL }
							alt={ __(
								'Google Logo',
								'google-listings-and-ads'
							) }
							width={ 24 }
							height={ 24 }
						/>
					</FlexItem>
					<FlexItem>
						{ __( 'Google', 'google-listings-and-ads' ) }
					</FlexItem>
					{ isDismissed && (
						<FlexItem className="gla-channel-visibility-get-started-is-dismissed">
							<GetStartedCTA />
						</FlexItem>
					) }
				</Flex>
			</FlexBlock>

			{ ! isDismissed && (
				<Flex className="gla-channel-visibility-google-ads-promo-content">
					<FlexBlock>
						<strong>
							{ __(
								'Get your products on Google',
								'google-listings-and-ads'
							) }
						</strong>
					</FlexBlock>
					<FlexBlock>
						<p>
							{ __(
								'Sync your products to reach customers when they’re searching for products like yours across Google',
								'google-listings-and-ads'
							) }
						</p>
					</FlexBlock>

					<FlexBlock>
						<GoogleAdsPromoCTA onDismiss={ handleDismiss } />
					</FlexBlock>
				</Flex>
			) }
		</Flex>
	);
};

export default GoogleAdsPromo;
