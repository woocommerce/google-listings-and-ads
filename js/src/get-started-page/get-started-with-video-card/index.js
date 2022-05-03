/**
 * External dependencies
 */
import {
	Card,
	CardHeader,
	CardBody,
	FlexBlock,
	Tip,
	__experimentalText as Text,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';
import WistiaVideo from '.~/components/wistia-video';
import googleLogoURL from '.~/images/google-logo.svg';
import { getSetupMCUrl } from '.~/utils/urls';
import './index.scss';

/**
 * @fires gla_setup_mc with `{ target: 'set_up_free_listings', trigger: 'click', context: 'get-started-with-video' }`.
 * @fires gla_documentation_link_click with `{ context: 'get-started-with-video', linkId: 'wp-terms-of-service', href: 'https://wordpress.com/tos/' }`.
 */
const GetStartedWithVideoCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;

	return (
		<Card className="gla-get-started-with-video-card" isBorderless>
			<CardHeader>
				<p>
					{ __(
						'The official extension for WooCommerce, built in collaboration with',
						'google-listings-and-ads'
					) }
				</p>
				<img
					src={ googleLogoURL }
					alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
					width="71"
					height="24"
				/>
			</CardHeader>
			<FlexBlock className="motivation-video">
				<WistiaVideo
					id="lpvgtsjwrg"
					src="https://fast.wistia.net/embed/iframe/lpvgtsjwrg?seo=false&videoFoam=true"
					title="WooCommerce-Google-Listings-Ads"
				/>
			</FlexBlock>
			<CardBody>
				<Text
					variant="title.medium"
					className="gla-get-started-with-video-card__title"
				>
					{ __(
						'Reach new customers across Google with free product listings and paid ads',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					variant="body"
					className="gla-get-started-with-video-card__description"
				>
					{ __(
						'Sync your products directly to Google, manage your product feed, and create Google Ad campaigns–without leaving your WooCommerce dashboard. The official extension, built in collaboration with Google.',
						'google-listings-and-ads'
					) }
				</Text>
				<AppButton
					className="gla-get-started-with-video-card__button"
					isPrimary
					disabled={ disableNextStep }
					href={ getSetupMCUrl() }
					eventName="gla_setup_mc"
					eventProps={ {
						target: 'set_up_free_listings',
						trigger: 'click',
						context: 'get-started-with-video',
					} }
				>
					{ __(
						'Start listing products →',
						'google-listings-and-ads'
					) }
				</AppButton>
				<Text className="gla-get-started-with-video-card__hint">
					{ __(
						'Estimated setup time: 15 min',
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
									context="get-started-with-video"
									linkId="wp-terms-of-service"
									href="https://wordpress.com/tos/"
								/>
							),
						}
					) }
				</Text>
			</CardBody>
			<Tip>
				{ __(
					'If you’re already using another extension to manage your product feed with Google, make sure to deactivate or uninstall it first to prevent duplicate product feeds.',
					'google-listings-and-ads'
				) }
			</Tip>
		</Card>
	);
};

export default GetStartedWithVideoCard;
