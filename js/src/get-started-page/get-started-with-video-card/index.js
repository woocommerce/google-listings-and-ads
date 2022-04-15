/**
 * External dependencies
 */
import {
	Card,
	Flex,
	FlexBlock,
	__experimentalText as Text,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';
import WistiaVideo from '.~/components/wistia-video';

const GetStartedWithVideoCard = () => {
	return (
		<Card
			className="woocommerce-marketing-google-get-started-with-video-card"
			isBorderless
		>
			<Flex>
				<FlexBlock className="motivation-video">
					<WistiaVideo
						id="lpvgtsjwrg"
						src="https://fast.wistia.net/embed/iframe/lpvgtsjwrg?seo=false&videoFoam=true"
						title="WooCommerce-Google-Listings-Ads"
					/>
				</FlexBlock>
				<FlexBlock className="motivation-text">
					<Text variant="title.medium" className="title">
						{ __(
							'Reach new customers across Google with free product listings and paid ads',
							'google-listings-and-ads'
						) }
					</Text>
				</FlexBlock>
			</Flex>
		</Card>
	);
};

export default GetStartedWithVideoCard;
