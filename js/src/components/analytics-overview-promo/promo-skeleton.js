/**
 * External dependencies
 */
import { Card, CardBody, Flex, FlexItem } from '@wordpress/components';

/**
 * Skeleton placeholder for the Analytics Overview promo, rendered while the data that
 * decides whether the promo shows is still resolving. Mirrors the layout of the promo Card.
 *
 * @return {JSX.Element} The promo skeleton.
 */
const PromoSkeleton = () => (
	<Card className="gla-analytics-overview-promo-skeleton">
		<CardBody size="large">
			<Flex
				align="flex-start"
				gap={ 8 }
				justify="flex-start"
				direction={ [ 'column', 'row' ] }
			>
				<FlexItem className="gla-analytics-overview-promo__image-wrapper">
					<span className="gla-analytics-overview-promo-skeleton__image" />
				</FlexItem>
				<FlexItem className="gla-analytics-overview-promo__content gla-analytics-overview-promo-skeleton__content">
					<span className="gla-analytics-overview-promo-skeleton__title" />
					<span className="gla-analytics-overview-promo-skeleton__description" />
					<Flex
						className="gla-analytics-overview-promo__actions"
						justify="flex-start"
						gap={ 2 }
					>
						<span className="gla-analytics-overview-promo-skeleton__button" />
						<span className="gla-analytics-overview-promo-skeleton__button" />
					</Flex>
				</FlexItem>
			</Flex>
		</CardBody>
	</Card>
);

export default PromoSkeleton;
