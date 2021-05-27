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
import { ReactComponent as ImageConnection } from './img-connection.svg';
import { ReactComponent as ImageFreeListings } from './img-free-listings.svg';
import { ReactComponent as ImageGoogldAds } from './img-googld-ads.svg';
import './index.scss';

const FeaturesCard = () => {
	return (
		<Card className="gla-get-started-features-card">
			<Flex>
				<FlexBlock>
					<ImageConnection viewBox="0 0 117 100" />
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
					<ImageFreeListings viewBox="0 0 100 100" />
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
					<ImageGoogldAds viewBox="0 0 104 100" />
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
