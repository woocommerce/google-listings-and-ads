/**
 * External dependencies
 */
import { FlexBlock, Card, CardBody, Tip } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import AppButton from '~/components/app-button';
import Text from '~/components/app-text';
import AppDocumentationLink from '~/components/app-documentation-link';
import { getOnboardingUrl } from '~/utils/urls';
import './index.scss';
import heroUrl from '~/images/get-started/hero.png';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';

/**
 * @fires gla_setup_mc with `{ triggered_by: 'start-onboarding-button', action: 'go-to-onboarding', context: 'get-started-with-hero' }`.
 * @fires gla_documentation_link_click with `{ context: 'get-started-with-hero', linkId: 'wp-terms-of-service', href: 'https://wordpress.com/tos/' }`.
 */
const GetStartedWithHeroCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;
	const isServiceBasedMerchant = useServiceBasedMerchant();

	const description = isServiceBasedMerchant
		? __(
				'Drive sales and find new customers wherever they are online including Google Search, Shopping, YouTube, and more.',
				'google-listings-and-ads'
		  )
		: __(
				'Effortlessly sync your WooCommerce product feed across Google and be seen by millions of engaged shoppers with the Google for WooCommerce extension.',
				'google-listings-and-ads'
		  );

	return (
		<Card className="gla-get-started-with-hero-card" isBorderless>
			<FlexBlock className="motivation">
				<div className="gla-get-started-with-hero-card__image">
					<img
						alt={ __(
							'Google for WooCommerce',
							'google-listings-and-ads'
						) }
						height="100%"
						src={ heroUrl }
						width="100%"
					/>
				</div>
			</FlexBlock>
			<CardBody>
				<Text
					className="gla-get-started-with-hero-card__caption"
					variant="caption"
				>
					{ __(
						'The official extension for WooCommerce, built in collaboration with Google',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					className="gla-get-started-with-hero-card__title"
					variant="title-medium"
				>
					{ __(
						'Connect your WooCommerce store and reach millions of shoppers on Google',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					className="gla-get-started-with-hero-card__description"
					variant="body"
				>
					{ description }
				</Text>
				<AppButton
					className="gla-get-started-with-hero-card__button"
					disabled={ disableNextStep }
					eventName="gla_setup_mc"
					eventProps={ {
						triggered_by: 'start-onboarding-button',
						action: 'go-to-onboarding',
						context: 'get-started-with-hero',
					} }
					href={ getOnboardingUrl() }
					isPrimary
				>
					{ __( 'Sell more on Google →', 'google-listings-and-ads' ) }
				</AppButton>
				<Text className="gla-get-started-with-hero-card__hint">
					{ __(
						'Estimated setup time: 5 min',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					className="gla-get-started-with-hero-card__terms-notice"
					variant="body"
				>
					{ createInterpolateElement(
						__(
							'By clicking ‘Sell more on Google’, you agree to our <link>Terms of Service.</link>',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="get-started-with-hero"
									href="https://wordpress.com/tos/"
									linkId="wp-terms-of-service"
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

export default GetStartedWithHeroCard;
