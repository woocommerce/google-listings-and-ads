/**
 * External dependencies
 */
import { FlexItem, Card, CardBody } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import AppDocumentationLink from '~/components/app-documentation-link';
import motivationImageURL from '~/images/get-started/motivation.svg';
import './index.scss';
import Text from '~/components/app-text';
import AppButton from '~/components/app-button';
import { getOnboardingUrl } from '~/utils/urls';

/**
 * @fires gla_setup_mc with `{ triggered_by: 'start-onboarding-button', action: 'go-to-onboarding', context: 'get-started' }`.
 * @fires gla_documentation_link_click with `{ context: 'get-started', linkId: 'wp-terms-of-service', href: 'https://wordpress.com/tos/' }`.
 */
const GetStartedCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;

	return (
		<Card className="gla-get-started-card" isBorderless>
			<FlexItem className="motivation-image">
				<img
					alt={ __(
						'Google Shopping search results example',
						'google-listings-and-ads'
					) }
					height="185"
					src={ motivationImageURL }
					width="279"
				/>
			</FlexItem>
			<CardBody>
				<Text
					className="gla-get-started-card__title"
					variant="title-medium"
				>
					{ __(
						'Get your products in front of more shoppers with Google for WooCommerce',
						'google-listings-and-ads'
					) }
				</Text>
				<AppButton
					disabled={ disableNextStep }
					eventName="gla_setup_mc"
					eventProps={ {
						triggered_by: 'start-onboarding-button',
						action: 'go-to-onboarding',
						context: 'get-started',
					} }
					href={ getOnboardingUrl() }
					isPrimary
				>
					{ __( 'Sell more on Google →', 'google-listings-and-ads' ) }
				</AppButton>
				<Text className="gla-get-started-card__terms-notice">
					{ createInterpolateElement(
						__(
							'By clicking ‘Sell more on Google‘, you agree to our <link>Terms of Service.</link>',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="get-started"
									href="https://wordpress.com/tos/"
									linkId="wp-terms-of-service"
								/>
							),
						}
					) }
				</Text>
			</CardBody>
		</Card>
	);
};

export default GetStartedCard;
