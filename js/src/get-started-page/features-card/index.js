/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	Flex,
	FlexBlock,
	__experimentalText as Text,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import connectionImageURL from './img-connection.svg';
import freeListingsImageURL from './img-free-listings.svg';
import googleAdsImageURL from './img-google-ads.svg';
import './index.scss';

const FeaturesCard = () => {
	return (
		<Card className="gla-get-started-features-card">
			<Flex>
				<FlexBlock>
					<img
						src={ connectionImageURL }
						alt={ __(
							'Drawing of jigsaw puzzles connecting together',
							'google-listings-and-ads'
						) }
						width="117"
						height="100"
					/>
					<Text
						className="gla-get-started-features-card__label"
						variant="label"
					>
						{ __(
							'Connect your store seamlessly',
							'google-listings-and-ads'
						) }
					</Text>
					<Text
						className="gla-get-started-features-card__content"
						variant="body"
					>
						{ __(
							'Sync your WooCommerce store data with Google Merchant Center to easily list your products across Google.',
							'google-listings-and-ads'
						) }
					</Text>
				</FlexBlock>
				<FlexBlock>
					<img
						src={ freeListingsImageURL }
						alt={ __(
							'Drawing of a person looking at their mobile',
							'google-listings-and-ads'
						) }
						width="100"
						height="100"
					/>
					<Text
						className="gla-get-started-features-card__label"
						variant="label"
					>
						{ __(
							'Reach online shoppers with free listings',
							'google-listings-and-ads'
						) }
					</Text>
					<Text
						className="gla-get-started-features-card__content"
						variant="body"
					>
						{ __(
							`Showcase eligible products to shoppers looking for what you offer and drive traffic to your store with Google's free listings.`,
							'google-listings-and-ads'
						) }
					</Text>
				</FlexBlock>
				<FlexBlock>
					<img
						src={ googleAdsImageURL }
						alt={ __(
							'Drawing of a bar and line charts heading up',
							'google-listings-and-ads'
						) }
						width="104"
						height="100"
					/>
					<Text
						className="gla-get-started-features-card__label"
						variant="label"
					>
						{ __(
							'Boost store traffic and sales with Google Ads',
							'google-listings-and-ads'
						) }
					</Text>
					<Text
						className="gla-get-started-features-card__content"
						variant="body"
					>
						{ __(
							'Create a Performance Max campaign to promote products across Google Search, Shopping, Gmail, YouTube, and the Display Network.',
							'google-listings-and-ads'
						) }
					</Text>
				</FlexBlock>
			</Flex>
		</Card>
	);
};

export default FeaturesCard;
