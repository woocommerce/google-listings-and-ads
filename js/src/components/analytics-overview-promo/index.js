/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { store as preferencesStore } from '@wordpress/preferences';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import usePreference from '~/hooks/usePreference';
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';
import AppButton from '~/components/app-button';
import { ANALYTICS_OVERVIEW_PROMO_KEY } from './constants';
import './index.scss';

const defaultDateRange =
	getSetting( 'wcAdminSettings' )?.woocommerce_default_date_range;

/**
 * Analytics overview promo section for the Analytics → Overview page, mounted by the
 * `woocommerce_dashboard_default_sections` filter registered in
 * `~/filters/analytics-overview-section`.
 *
 * @param {Object} props Props core passes down (path, query, title, controls, etc.).
 * @param {Object} [props.query] The URL query params carrying the selected range.
 * @return {JSX.Element|null} Analytics overview promo component, or `null` while resolving or once dismissed.
 */
const AnalyticsOverviewPromo = ( { query = {} } ) => {
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( ANALYTICS_OVERVIEW_PROMO_KEY );
	const { hasFinishedResolution, isDown, metricsCase } =
		useProductRevenueMetricsDown( query, defaultDateRange );

	if ( isDismissed || ! hasFinishedResolution ) {
		return null;
	}

	const handleDismiss = () => {
		set( PREFERENCES_STORE_NAMESPACE, ANALYTICS_OVERVIEW_PROMO_KEY, true );
	};

	return (
		<Flex
			className="gla-analytics-overview-promo"
			align="center"
			justify="space-between"
			gap={ 3 }
		>
			<FlexBlock>
				{ isDown
					? sprintf(
							// translators: %s: the matched metrics case, e.g. "revenue" or "products".
							__( 'Metrics down: %s', 'google-listings-and-ads' ),
							metricsCase
					  )
					: __( 'Metrics not down', 'google-listings-and-ads' ) }
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
