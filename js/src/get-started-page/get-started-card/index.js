/**
 * External dependencies
 */
import {
	Card,
	Flex,
	FlexItem,
	FlexBlock,
	__experimentalText as Text,
	Button,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import { recordSetupMCEvent } from '.~/utils/recordEvent';
import { ReactComponent as GoogleShoppingImage } from './image.svg';
import { glaData } from '.~/constants';
import './index.scss';

const GetStartedCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;
	const handleClick = () => {
		recordSetupMCEvent( 'get_started' );
		getHistory().push( getNewPath( {}, '/google/setup-mc' ) );
	};

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
							'Reach more shoppers and drive sales for your store. Integrate with Google Merchant Center to list your products for free and launch paid ad campaigns.',
							'google-listings-and-ads'
						) }
					</Text>
					<Button
						isPrimary
						disabled={ disableNextStep }
						onClick={ handleClick }
					>
						{ __( 'Get started', 'google-listings-and-ads' ) }
					</Button>
					<Text className="woocommerce-marketing-google-get-started-card__terms-notice">
						{ createInterpolateElement(
							__(
								'By clicking ‘Get started’, you agree to our <link>Terms of Service.</link>',
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
