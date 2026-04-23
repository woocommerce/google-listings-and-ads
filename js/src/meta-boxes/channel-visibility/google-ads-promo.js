/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import usePreference from '~/hooks/usePreference';
import googleLogoURL from '~/images/logo/google-g-logo.svg';
import { recordGlaEvent } from '~/utils/tracks';
import {
	CHANNEL_VISIBILITY_PROMO_KEY,
	CHANNEL_VISIBILITY_CONTEXT,
} from './constants';
import GetStartedCTA from './get-started-cta';
import PromoCTA from './promo-cta';
import ChannelVisibilitySettings from './channel-visibility-settings';
import AppSpinner from '~/components/app-spinner';
import './google-ads-promo.scss';

/**
 * Google Ads Promo banner is shown.
 *
 * @event gla_google_ads_promo_shown
 * @property {string} context Context of the Google Ads Promo.
 */

/**
 * Google Ads Promo component.
 *
 * @fires gla_google_ads_promo_shown with `{ context: channel-visibility-meta-box }`.
 *
 * @return {JSX.Element} The Google Ads Promo component
 */
const GoogleAdsPromo = () => {
	const {
		hasGoogleMCConnection,
		hasFinishedResolution: hasResolvedMCConnection,
	} = useGoogleMCAccount();
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( CHANNEL_VISIBILITY_PROMO_KEY );
	const hasTrackedRef = useRef( false );

	useEffect( () => {
		if ( ! hasTrackedRef.current && hasResolvedMCConnection ) {
			recordGlaEvent( 'gla_google_ads_promo_shown', {
				context: CHANNEL_VISIBILITY_CONTEXT,
			} );
			hasTrackedRef.current = true;
		}
	}, [ hasResolvedMCConnection ] );

	const handleDismiss = () => {
		set( PREFERENCES_STORE_NAMESPACE, CHANNEL_VISIBILITY_PROMO_KEY, true );
	};

	if ( ! hasResolvedMCConnection ) {
		return <AppSpinner />;
	}

	if ( hasGoogleMCConnection ) {
		return <ChannelVisibilitySettings />;
	}

	return (
		<Flex className="gla-channel-visibility" direction="column" gap={ 4 }>
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="flex-start">
					<FlexItem>
						<img
							className="gla-channel-visibility__logo"
							src={ googleLogoURL }
							alt={ __(
								'Google Logo',
								'google-listings-and-ads'
							) }
							width={ 16 }
							height={ 16 }
						/>
					</FlexItem>
					<FlexItem>
						{ __( 'Google', 'google-listings-and-ads' ) }
					</FlexItem>
					{ isDismissed && (
						<FlexItem className="gla-channel-visibility__get-started--is-dismissed">
							<GetStartedCTA />
						</FlexItem>
					) }
				</Flex>
			</FlexBlock>

			{ ! isDismissed && (
				<Flex
					className="gla-channel-visibility__content"
					direction="column"
					gap={ 3 }
				>
					<FlexBlock>
						<h3 className="gla-channel-visibility__title">
							{ __(
								'Get your products on Google',
								'google-listings-and-ads'
							) }
						</h3>
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
						<PromoCTA onDismiss={ handleDismiss } />
					</FlexBlock>
				</Flex>
			) }
		</Flex>
	);
};

export default GoogleAdsPromo;
