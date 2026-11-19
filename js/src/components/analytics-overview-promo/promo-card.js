/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { Card, CardBody, Flex, FlexItem } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';
import { recordGlaEvent } from '~/utils/tracks';
import promoImage from '~/images/analytics-promo.png';
import { ANALYTICS_OVERVIEW_PROMO_CONTEXT } from './constants';
import PromoText from './promo-text';
import PromoActions from './promo-actions';
import './promo-card.scss';

const defaultDateRange =
	getSetting( 'wcAdminSettings' )?.woocommerce_default_date_range;

/**
 * The placement is shown. Re-fires whenever the shown case changes (guarded on
 * `case` + shown-state, not on mount alone), so a date-range switch that hides,
 * re-shows, or swaps the matched case while the section stays mounted is captured.
 *
 * @event gla_analytics_in_product_placements_view
 * @property {string} context Where the placement is shown.
 * @property {string} case Which metrics-down case matched, `'revenue'` or `'products'`.
 */

/**
 * Promo Card content, shown when the merchant's store metrics are trending down and there
 * has been no recent Google Ads spend. Holds the data hooks, so they only run while the
 * promo is not dismissed.
 *
 * @fires gla_analytics_in_product_placements_view
 *
 * @param {Object} props
 * @param {Object} props.query The URL query params carrying the selected range.
 * @return {JSX.Element|null} The promo Card, or `null` when the promo should not show.
 */
const PromoCard = ( { query } ) => {
	const { hasAdSpend, hasFinishedResolution: hasResolvedAdSpend } =
		useHasRecentAdSpend();
	const { isDown, metricsCase } = useProductRevenueMetricsDown(
		query,
		defaultDateRange
	);

	const shouldShow = hasResolvedAdSpend && isDown && ! hasAdSpend;

	useEffect( () => {
		if ( shouldShow ) {
			recordGlaEvent( 'gla_analytics_in_product_placements_view', {
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: metricsCase,
			} );
		}
	}, [ metricsCase, shouldShow ] );

	if ( ! shouldShow ) {
		return null;
	}

	return (
		<Card className="gla-analytics-overview-promo-card">
			<CardBody size="large">
				<Flex
					align="flex-start"
					gap={ 8 }
					justify="flex-start"
					direction={ [ 'column', 'row' ] }
				>
					<FlexItem className="gla-analytics-overview-promo-card__image-wrapper">
						<img
							className="gla-analytics-overview-promo-card__image"
							src={ promoImage }
							width="136"
							height="116"
							alt=""
						/>
					</FlexItem>
					<FlexItem className="gla-analytics-overview-promo-card__content">
						<PromoText metricsCase={ metricsCase } />
						<PromoActions metricsCase={ metricsCase } />
					</FlexItem>
				</Flex>
			</CardBody>
		</Card>
	);
};

export default PromoCard;
