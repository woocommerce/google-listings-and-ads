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
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppButton from '.~/components/app-button';
import WistiaVideo from '.~/components/wistia-video';
import './index.scss';

const GetStartedWithVideoCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;

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
					<Text variant="body" className="description">
						{ __(
							'Sync your products directly to Google, manage your product feed, and create Google Ad campaigns–without leaving your WooCommerce dashboard. The official extension, built in collaboration with Google.',
							'google-listings-and-ads'
						) }
					</Text>
					<AppButton
						className="button"
						isPrimary
						disabled={ disableNextStep }
						href={ getNewPath( {}, '/google/setup-mc' ) }
						eventName="gla_setup_mc"
						eventProps={ {
							target: 'set_up_free_listings',
							trigger: 'click',
						} }
					>
						{ __(
							'Start listing products →',
							'google-listings-and-ads'
						) }
					</AppButton>
					<Text className="hint">
						{ __(
							'Estimated setup time: 15 min ',
							'google-listings-and-ads'
						) }
					</Text>
				</FlexBlock>
			</Flex>
		</Card>
	);
};

export default GetStartedWithVideoCard;
