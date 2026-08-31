/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import usePreference from '~/hooks/usePreference';
import AppButton from '~/components/app-button';
import { ANALYTICS_OVERVIEW_PROMO_KEY } from './constants';
import './index.scss';

/**
 * Analytics overview promo section for the Analytics → Overview page, mounted by the
 * `woocommerce_dashboard_default_sections` filter registered in
 * `~/filters/analytics-overview-section`.
 *
 * @return {JSX.Element|null} Analytics overview promo component, or `null` once dismissed.
 */
const AnalyticsOverviewPromo = () => {
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( ANALYTICS_OVERVIEW_PROMO_KEY );

	const handleDismiss = () => {
		set( PREFERENCES_STORE_NAMESPACE, ANALYTICS_OVERVIEW_PROMO_KEY, true );
	};

	if ( isDismissed ) {
		return null;
	}

	return (
		<Flex
			className="gla-analytics-overview-promo"
			align="center"
			justify="space-between"
			gap={ 3 }
		>
			<FlexBlock>
				{ __( 'placeholder', 'google-listings-and-ads' ) }
			</FlexBlock>
			<FlexItem>
				<AppButton onClick={ handleDismiss } isTertiary>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexItem>
		</Flex>
	);
};

export default AnalyticsOverviewPromo;
