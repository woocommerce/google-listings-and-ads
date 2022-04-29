/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	FlexItem,
	__experimentalText as Text,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppDocumentationLink from '.~/components/app-documentation-link';
import motivationImageURL from './image.svg';
import './index.scss';
import AppButton from '.~/components/app-button';
import { getSetupMCUrl } from '.~/utils/urls';

/**
 * @fires gla_setup_mc with `{ target: 'set_up_free_listings', trigger: 'click' }`.
 */
const GetStartedCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;

	return (
		<Card className="gla-get-started-card" isBorderless>
			<FlexItem className="motivation-image">
				<img
					src={ motivationImageURL }
					alt={ __(
						'Google Shopping search results example',
						'google-listings-and-ads'
					) }
					width="279"
					height="185"
				/>
			</FlexItem>
			<CardBody>
				<Text
					variant="title.medium"
					className="gla-get-started-card__title"
				>
					{ __(
						'Get your products in front of more shoppers with Google Listings & Ads',
						'google-listings-and-ads'
					) }
				</Text>
				<AppButton
					isPrimary
					disabled={ disableNextStep }
					href={ getSetupMCUrl() }
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
				<Text className="gla-get-started-card__terms-notice">
					{ createInterpolateElement(
						__(
							'By clicking ‘Start listing products‘, you agree to our <link>Terms of Service.</link>',
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
			</CardBody>
		</Card>
	);
};

export default GetStartedCard;
