/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	Flex,
	FlexBlock,
	__experimentalText as Text,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import freeListingsImageURL from './img-free-listings.svg';
import productPromotionImageURL from './img-product-promotion.svg';
import dashboardImageURL from './img-dashboard.svg';
import './index.scss';

const LearnMoreLink = ( { linkId, href } ) => {
	return (
		<Text
			className="gla-get-started-features-card__learn-more"
			variant="body"
		>
			{ createInterpolateElement(
				__( '<link>Learn More →</link>', 'google-listings-and-ads' ),
				{
					link: (
						<AppDocumentationLink
							context="get-started"
							linkId={ linkId }
							href={ href }
						/>
					),
				}
			) }
		</Text>
	);
};

const FeaturesCard = () => {
	return (
		<Card className="gla-get-started-features-card" isBorderless>
			<CardHeader>
				<Text
					className="gla-get-started-features-card__title"
					variant="title.medium"
				>
					{ __(
						'49% of shoppers surveyed say they use Google to discover or find a new item or product',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					className="gla-get-started-features-card__description"
					variant="body"
				>
					{ __(
						'With Google Listings & Ads, connect with the right shoppers at the right moment when they’re looking to buy products like yours.',
						'google-listings-and-ads'
					) }
				</Text>
			</CardHeader>
			<Flex gap={ 0 }>
				<FlexBlock>
					<img
						src={ freeListingsImageURL }
						alt={ __(
							'Drawing of WooCommerce and Google',
							'google-listings-and-ads'
						) }
						width="183"
						height="100"
					/>
					<Text
						className="gla-get-started-features-card__label"
						variant="label"
					>
						{ __(
							'Show products automatically on Google for free',
							'google-listings-and-ads'
						) }
					</Text>
					<Text
						className="gla-get-started-features-card__content"
						variant="body"
					>
						{ __(
							'When your products are added and approved, they’ll be eligible for free listings, reaching shoppers across Google’s network.',
							'google-listings-and-ads'
						) }
					</Text>
					<LearnMoreLink
						linkId="get-started-features-free-listing-learn-more"
						href="https://woocommerce.com/document/google-listings-and-ads/#free-listings-on-google"
					/>
				</FlexBlock>
				<FlexBlock>
					<img
						src={ productPromotionImageURL }
						alt={ __(
							'Drawing of a mobile and product ads',
							'google-listings-and-ads'
						) }
						width="183"
						height="100"
					/>
					<Text
						className="gla-get-started-features-card__label"
						variant="label"
					>
						{ __(
							'Promote products and drive more sales with paid ads',
							'google-listings-and-ads'
						) }
					</Text>
					<Text
						className="gla-get-started-features-card__content"
						variant="body"
					>
						{ __(
							'Connect your Google Ads account, choose a budget, and Google will optimize your ads so they appear at the right time and place. ',
							'google-listings-and-ads'
						) }
					</Text>
					<LearnMoreLink
						linkId="get-started-features-google-ads-learn-more"
						href="https://woocommerce.com/document/google-listings-and-ads/#google-performance-max-campaigns"
					/>
				</FlexBlock>
				<FlexBlock>
					<img
						src={ dashboardImageURL }
						alt={ __(
							'Drawing of a bar and line charts heading up',
							'google-listings-and-ads'
						) }
						width="183"
						height="100"
					/>
					<Text
						className="gla-get-started-features-card__label"
						variant="label"
					>
						{ __(
							'Track performance straight from your store dashboard',
							'google-listings-and-ads'
						) }
					</Text>
					<Text
						className="gla-get-started-features-card__content"
						variant="body"
					>
						{ __(
							'Real-time reporting all within your WooCommerce dashboard means you know how your campaigns are performing at all times.',
							'google-listings-and-ads'
						) }
					</Text>
					<LearnMoreLink
						linkId="get-started-features-dashboard-learn-more"
						href="https://woocommerce.com/document/google-listings-and-ads/#getting-started-with-campaign-analytics"
					/>
				</FlexBlock>
			</Flex>
		</Card>
	);
};

export default FeaturesCard;
