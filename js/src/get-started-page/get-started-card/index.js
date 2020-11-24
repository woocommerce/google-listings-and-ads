/**
 * External dependencies
 */
import {
	Button,
	Card,
	Flex,
	FlexItem,
	FlexBlock,
	__experimentalText as Text,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ReactComponent as GoogleShoppingImage } from './image.svg';
import './index.scss';

const GetStartedCard = () => {
	// TODO: add event handler for clicking Get started button.
	const handleClick = () => {};

	return (
		<Card className="woocommerce-marketing-google-get-started-card">
			<Flex>
				<FlexBlock className="motivation-text">
					<Text variant="title.medium" className="title">
						{ __(
							'List your products on Google Shopping, for free',
							'google-listings-and-ads'
						) }
					</Text>
					<Text variant="body" className="description">
						{ __(
							'Integrate with Google’s Merchant Center to list your products for free on Google. Optionally, create paid Smart Shopping campaigns to boost your sales.',
							'google-listings-and-ads'
						) }
					</Text>
					<Button isPrimary onClick={ handleClick } href="admin.php?page=wc-admin&path=%2Fsetup-mc">
						{ __( 'Get started', 'google-listings-and-ads' ) }
					</Button>
				</FlexBlock>
				<FlexItem className="motivation-image">
					<GoogleShoppingImage viewBox="0 0 416 394"></GoogleShoppingImage>
				</FlexItem>
			</Flex>
		</Card>
	);
};

export default GetStartedCard;
