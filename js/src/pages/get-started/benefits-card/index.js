/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody, Flex, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import benefitsImageURL from '~/images/get-started/benefits.png';
import Text from '~/components/app-text';
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
				<Flex
					className="gla-get-started-benefits-card__content"
					direction="column"
					gap={ 4 }
					justify="center"
				>
					<Flex direction="column" gap={ 2 }>
						<Text variant="title-small">
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
								'Reach more customers by advertising your products across Google Ads channels like Search, YouTube and Discover.',
								'google-listings-and-ads'
							) }
						</Text>
					</Flex>
					<Notice
						className="gla-get-started-benefits-card__notice"
						status="success"
						isDismissible={ false }
					>
						<p>
							{ __(
								'Choose your offer and get up to $1500 in Ads credits. New advertiser? Choose between three offers, based on your monthly budget, to jumpstart your first campaign!',
								'google-listings-and-ads'
							) }
						</p>
					</Notice>
				</Flex>
			</CardBody>
		</Card>
	);
};

export default BenefitsCard;
