/**
 * External dependencies
 */
import { FlexBlock, Card, CardBody, Tip } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppButton from '.~/components/app-button';
import Text from '.~/components/app-text';
import AppDocumentationLink from '.~/components/app-documentation-link';
import { getSetupMCUrl } from '.~/utils/urls';
import './index.scss';
import heroUrl from './hero.png';

/**
 * @fires gla_setup_mc with `{ triggered_by: 'start-onboarding-button', action: 'go-to-onboarding', context: 'get-started-with-hero' }`.
 * @fires gla_documentation_link_click with `{ context: 'get-started-with-hero', linkId: 'wp-terms-of-service', href: 'https://wordpress.com/tos/' }`.
 */
const GetStartedWithHeroCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;

	return (
		<Card className="gla-get-started-with-hero-card" isBorderless>
			<FlexBlock className="motivation">
				<div className="gla-get-started-with-hero-card__image">
					<img
						src={ heroUrl }
						alt={ __(
							'Google for WooCommerce',
							'google-listings-and-ads'
						) }
						width="100%"
						height="100%"
					/>
				</div>
			</FlexBlock>
			<CardBody>
				<Text
					variant="caption"
					className="gla-get-started-with-hero-card__caption"
				>
					{ __(
						'The official extension for WooCommerce, built in collaboration with Google',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					variant="title-medium"
					className="gla-get-started-with-hero-card__title"
				>
					{ __(
						'Connect your WooCommerce store and reach millions of shoppers on Google',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					variant="body"
					className="gla-get-started-with-hero-card__description"
				>
					{ __(
						'Effortlessly sync your WooCommerce product feed across Google and be seen by millions of engaged shoppers with the Google for WooCommerce extension.',
						'google-listings-and-ads'
					) }
				</Text>
				<AppButton
					className="gla-get-started-with-hero-card__button"
					isPrimary
					disabled={ disableNextStep }
					href={ getSetupMCUrl() }
					eventName="gla_setup_mc"
					eventProps={ {
						triggered_by: 'start-onboarding-button',
						action: 'go-to-onboarding',
						context: 'get-started-with-hero',
					} }
				>
					{ __(
						'Sell more on Google →',
						'google-listings-and-ads'
					) }
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

export default GetStartedWithHeroCard;
