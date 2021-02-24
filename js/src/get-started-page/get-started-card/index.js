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
import { Link } from '@woocommerce/components';
import classnames from 'classnames';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { recordSetupMCEvent } from '../../utils/recordEvent';
import { ReactComponent as GoogleShoppingImage } from './image.svg';
import './index.scss';

const GetStartedCard = () => {
	const handleClick = () => {
		recordSetupMCEvent( 'get_started' );
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
					<Link
						className={ classnames(
							'components-button',
							'is-primary'
						) }
						href={ getNewPath( {}, '/google/setup-mc' ) }
						onClick={ handleClick }
					>
						{ __( 'Get started', 'google-listings-and-ads' ) }
					</Link>
					<Text className="woocommerce-marketing-google-get-started-card__terms-notice">
						{ createInterpolateElement(
							__(
								'By clicking ‘Get started’, you agree to our <link>Terms of Service.</link>',
								'google-listings-and-ads'
							),
							{
								link: (
									<Link
										type="external"
										target="_blank"
										rel="noopener noreferrer"
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
