/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	FlexItem,
	__experimentalText as Text,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import benefitsImageURL from './image.svg';
import './index.scss';

const BenefitsCard = () => {
	return (
		<Card className="gla-get-started-benefits-card" isBorderless>
			<CardBody>
				<img
					src={ benefitsImageURL }
					alt={ __(
						'Google Listings & Ads Benefits',
						'google-listings-and-ads'
					) }
					width="100%"
					height="100%"
				/>
				<FlexItem>
					<Text
						variant="title.medium"
						className="components-flex__item__title"
					>
						{ __(
							'Increase clicks by',
							'google-listings-and-ads'
						) }
					</Text>
					<Text
						variant="body"
						className="components-flex__item__description"
					>
						{ __(
							'Using free listings and ads together increased clicks by 50% and doubled impressions. Small-to-medium merchants saw the largest share of the increases.',
							'google-listings-and-ads'
						) }
					</Text>
					<Text className="components-flex__item__hint">
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
