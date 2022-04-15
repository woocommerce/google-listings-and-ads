/**
 * External dependencies
 */
import { Card, Flex, FlexBlock } from '@wordpress/components';

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
			</Flex>
		</Card>
	);
};

export default GetStartedWithVideoCard;
