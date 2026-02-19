/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';
import GoogleAdsPromoCTA from './google-ads-promo-cta';
import GoogleAdsPromoSetupCompleted from './google-ads-promo-setup-completed';
import { glaData } from '~/constants';
import './google-ads-promo.scss';

const { adsSetupComplete } = glaData;

/**
 * Google Ads Promo component.
 *
 * @return {JSX.Element|null} The Google Ads Promo component or null.
 */
const GoogleAdsPromo = () => {
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
				</Flex>
			</FlexBlock>

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
					<GoogleAdsPromoCTA />
				</FlexBlock>
			</Flex>
		</Flex>
	);
};

export default GoogleAdsPromo;
