/**
 * External dependencies
 */
import {
	Card,
	Flex,
	FlexItem,
	FlexBlock,
	__experimentalText as Text,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import { ReactComponent as GoogleShoppingImage } from './image.svg';
import { glaData } from '.~/constants';
import './index.scss';
import AppButton from '.~/components/app-button';

const GetStartedCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;

	return (
		<Card className="woocommerce-marketing-google-get-started-card">
			<Flex>
				<FlexBlock className="motivation-text">
					<Text variant="title.medium" className="title">
						{ __(
							'List your products on Google, for free and with ads',
							'google-listings-and-ads'
						) }
					</Text>
					<Text variant="body" className="description">
						{ __(
							'Reach more shoppers and drive sales for your store. Integrate with Google to list your products for free and launch paid ad campaigns.',
							'google-listings-and-ads'
						) }
					</Text>
					<AppButton
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
							'Set up free listings in Google',
							'google-listings-and-ads'
						) }
					</AppButton>
					<Text className="woocommerce-marketing-google-get-started-card__terms-notice">
						{ createInterpolateElement(
							__(
								'By clicking ‘Set up free listings in Google’, you agree to our <link>Terms of Service.</link>',
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
				<FlexItem className="motivation-image">
					<GoogleShoppingImage viewBox="0 0 416 394"></GoogleShoppingImage>
				</FlexItem>
			</Flex>
		</Card>
	);
};

export default GetStartedCard;
