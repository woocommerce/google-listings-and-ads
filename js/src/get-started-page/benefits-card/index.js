/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody, FlexItem } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import benefitsImageURL from './image.png';
import Text from '.~/components/app-text';
import './index.scss';

const BenefitsCard = () => {
	return (
		<Card className="gla-get-started-benefits-card" isBorderless>
			<CardBody>
				<div className="gla-get-started-benefits-card__image">
					<img
						src={ benefitsImageURL }
						alt={ __(
							'Google Listings & Ads Benefits',
							'google-listings-and-ads'
						) }
						width="100%"
						height="100%"
					/>
				</div>
				<FlexItem>
					<Text
						variant="title-medium"
						className="gla-get-started-benefits-card__title"
					>
						{ createInterpolateElement(
							__(
								'Increase clicks by <strong>50%</strong>',
								'google-listings-and-ads'
							),
							{ strong: <strong /> }
						) }
					</Text>
					<Text
						variant="body"
						className="gla-get-started-benefits-card__description"
					>
						{ __(
							'Using free listings and ads together increased clicks by 50% and doubled impressions. Small-to-medium merchants saw the largest share of the increases.',
							'google-listings-and-ads'
						) }
					</Text>
					<Text className="gla-get-started-benefits-card__hint">
						{ __(
							'Source: Google Internal Data, July 2020',
							'google-listings-and-ads'
						) }
					</Text>
				</FlexItem>
			</CardBody>
		</Card>
	);
};

export default BenefitsCard;
