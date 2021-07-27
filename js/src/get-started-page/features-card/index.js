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
import { glaData } from '.~/constants';
import './index.scss';

/**
 * Full URL to the feature images folder.
 */
const imagesURL = glaData.assetsURL + 'js/src/get-started-page/features-card/';

const FeaturesCard = () => {
	return (
		<Card className="gla-get-started-features-card">
			<Flex>
				<FlexBlock>
					<img
						src={ imagesURL + 'img-connection.svg' }
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
						src={ imagesURL + 'img-free-listings.svg' }
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
						src={ imagesURL + 'img-google-ads.svg' }
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
							'Create a Smart Shopping campaign to promote products across Google Search, Shopping, Gmail, YouTube, and the Display Network.',
							'google-listings-and-ads'
						) }
					</Text>
				</FlexBlock>
			</Flex>
		</Card>
	);
};

export default FeaturesCard;
