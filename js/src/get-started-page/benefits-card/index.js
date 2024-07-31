/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody, FlexItem } from '@wordpress/components';

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
							'Google for WooCommerce Benefits',
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
						{ __(
							'Reach your sales goals by creating a campaign',
							'google-listings-and-ads'
						) }
					</Text>
					<Text
						variant="body"
						className="gla-get-started-benefits-card__description"
					>
						{ __(
							'Reach more customers by advertising your products across Google Ads channels like Search, YouTube and Discover. Set up your campaign now so your products are included as soon as they’re approved.',
							'google-listings-and-ads'
						) }
					</Text>
				</FlexItem>
			</CardBody>
		</Card>
	);
};

export default BenefitsCard;
