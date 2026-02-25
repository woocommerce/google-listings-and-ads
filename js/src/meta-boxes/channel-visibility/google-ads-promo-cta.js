/**
 * External dependencies
 */
import { Flex, FlexBlock } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import GetStartedCTA from './get-started-cta';

const GoogleAdsPromoCTA = ( { context, onDismiss } ) => {
	return (
		<Flex gap={ 4 }>
			<FlexBlock>
				<GetStartedCTA context={ context } />
			</FlexBlock>

			<FlexBlock>
				<AppButton
					eventName="gla_google_ads_promo_dismiss_click"
					eventProps={ {
						context,
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

export default GoogleAdsPromoCTA;
