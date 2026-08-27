/**
 * External dependencies
 */
import { Card, CardBody, Flex, Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import Text from '~/components/app-text';
import benefitsImageURL from '~/images/get-started/benefits.jpg';
import './index.scss';

/**
 * Highlights the benefits of creating a Google Ads campaign,
 * The component is used in the "Get Started" page to encourage merchants to create their first campaign.
 *
 * @fires gla_documentation_link_click with `{ context: 'get-started', link_id: 'benefits-card-credit-terms', href: 'https://ads.google.com/home/terms-and-conditions/incentives/' }` when the user clicks on the "Terms and conditions" link in the notice.
 */
const BenefitsCard = () => {
	return (
		<Card className="gla-get-started-benefits-card" isBorderless>
			<CardBody>
				<div className="gla-get-started-benefits-card__image">
					<img
						alt={ __(
							'Google for WooCommerce Benefits',
							'google-listings-and-ads'
						) }
						height="100%"
						src={ benefitsImageURL }
						width="100%"
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
							className="gla-get-started-benefits-card__description"
							variant="body"
						>
							{ __(
								'Reach more customers by advertising your products across Google Ads channels like Search, YouTube and Discover.',
								'google-listings-and-ads'
							) }
						</Text>
					</Flex>
					<Notice
						className="gla-get-started-benefits-card__notice"
						isDismissible={ false }
						status="info"
					>
						<p>
							{ __(
								'Get $500 USD or more in Google Ads credits*. New advertiser? Choose between three offers, based on your monthly budget, to jumpstart your first campaign!',
								'google-listings-and-ads'
							) }
						</p>
					</Notice>
					<p className="gla-get-started-benefits-card__terms">
						{ createInterpolateElement(
							__(
								'* <link>Terms and conditions</link> apply.',
								'google-listings-and-ads'
							),
							{
								link: (
									<AppDocumentationLink
										className="gla-get-started-benefits-card__terms-link"
										context="get-started"
										href="https://ads.google.com/home/terms-and-conditions/incentives/"
										linkId="benefits-card-credit-terms"
									/>
								),
							}
						) }
					</p>
				</Flex>
			</CardBody>
		</Card>
	);
};

export default BenefitsCard;
