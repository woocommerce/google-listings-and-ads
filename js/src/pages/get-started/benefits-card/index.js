/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	Flex,
	FlexItem,
	Icon,
	Notice,
} from '@wordpress/components';
import { info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import benefitsImageURL from '~/images/get-started/benefits.png';
import Text from '~/components/app-text';
import AppDocumentationLink from '~/components/app-documentation-link';
import './index.scss';

/**
 * @fires gla_documentation_link_click with `{ context: 'get-started', link_id: 'benefits-ads-credit-terms', href: 'https://www.google.com/ads/coupons/terms/' }`
 */
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
					gap={ 3 }
					justify="center"
				>
					<Flex direction="column" gap={ 1 }>
						<Text
							variant="title-small"
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
								'Reach more customers by advertising your products across Google Ads channels like Search, YouTube and Discover.',
								'google-listings-and-ads'
							) }
						</Text>
					</Flex>
					<Notice
						className="gla-get-started-benefits-card__notice"
						status="info"
						isDismissible={ false }
					>
						<Flex align="flex-start" gap={ 3 }>
							<FlexItem className="gla-get-started-benefits-card__notice-icon">
								<Icon icon={ info } size={ 24 } />
							</FlexItem>
							<FlexItem>
								<Flex direction="column" gap={ 1 }>
									<p className="gla-get-started-benefits-card__notice-title">
										{ __(
											'Choose your offer and get up to $1500 in Ads credits*',
											'google-listings-and-ads'
										) }
									</p>
									<p>
										{ __(
											'New advertiser? Choose between three offers, based on your monthly budget, to jumpstart your first campaign!',
											'google-listings-and-ads'
										) }
									</p>
									<p>
										<AppDocumentationLink
											className="gla-get-started-benefits-card__notice-terms"
											context="get-started"
											linkId="benefits-ads-credit-terms"
											href="https://www.google.com/ads/coupons/terms/"
										>
											{ __(
												'*Terms and conditions',
												'google-listings-and-ads'
											) }
										</AppDocumentationLink>
									</p>
								</Flex>
							</FlexItem>
						</Flex>
					</Notice>
				</Flex>
			</CardBody>
		</Card>
	);
};

export default BenefitsCard;
