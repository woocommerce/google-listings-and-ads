/**
 * External dependencies
 */
import {
	Card,
	Flex,
	FlexBlock,
	Tip,
	__experimentalText as Text,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';
import WistiaVideo from '.~/components/wistia-video';
import googleLogoURL from '.~/images/google-logo.svg';
import './index.scss';

const GetStartedWithVideoCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;

	return (
		<Card className="gla-get-started-with-video-card" isBorderless>
			<Flex>
				<FlexBlock className="header">
					<Text variant="body" className="title">
						{ __(
							'The official extension for WooCommerce, built in collaboration with',
							'google-listings-and-ads'
						) }
					</Text>
					<div id="google-img">
						<img
							src={ googleLogoURL }
							alt={ __(
								'Google Logo',
								'google-listings-and-ads'
							) }
							width="71"
							height="24"
						/>
					</div>
				</FlexBlock>
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
					<Text
						className="gla-get-started-with-video-card__terms-notice"
						variant="body"
					>
						{ createInterpolateElement(
							__(
								'By clicking ‘Start listing products’, you agree to our <link>Terms of Service.</link>',
								'google-listings-and-ads'
							),
							{
								link: (
									<AppDocumentationLink
										context="get-started"
										linkId="wp-terms-of-service"
										href="https://wordpress.com/tos/"
									/>
								),
							}
						) }
					</Text>
				</FlexBlock>
				<Tip>
					{ __(
						'If you’re already using another extension to manage your product feed with Google, make sure to deactivate or uninstall it first to prevent duplicate product feeds.',
						'google-listings-and-ads'
					) }
				</Tip>
			</Flex>
		</Card>
	);
};

export default GetStartedWithVideoCard;
