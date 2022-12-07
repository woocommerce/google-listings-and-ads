/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, Flex, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';
import quoteImageURL from './img-quote.svg';
import Text from '.~/components/app-text';

const Quote = ( { quote, name } ) => {
	return (
		<FlexBlock>
			<img
				src={ quoteImageURL }
				alt={ __( 'An image of a quote.', 'google-listings-and-ads' ) }
				width="34"
				height="34"
			/>
			<Text
				className="gla-get-started-customer-quotes-card__content"
				variant="body"
			>
				{ quote }
			</Text>
			<Text className="gla-get-started-customer-quotes-card__name">
				{ name }
			</Text>
		</FlexBlock>
	);
};

const CustomerQuotesCard = () => {
	return (
		<Card className="gla-get-started-customer-quotes-card" isBorderless>
			<CardHeader>
				<Text
					className="gla-get-started-customer-quotes-card__title"
					variant="title-medium"
				>
					{ __(
						'21,000+ WooCommerce store owners like you already list products with Google',
						'google-listings-and-ads'
					) }
				</Text>
			</CardHeader>
			<Flex gap={ 0 }>
				<Quote
					quote={ __(
						'Thank you Google and WooCommerce for creating this app. It’s so simple to use and connect your products to Merchant Center.',
						'google-listings-and-ads'
					) }
					name={ __( 'joshualukewhite', 'google-listings-and-ads' ) }
				/>
				<Quote
					quote={ __(
						'It does everything smoothly. Perfect and must need add-on from WooCommerce. Some things are just “essentials”.',
						'google-listings-and-ads'
					) }
					name={ __( 'Anonymous', 'google-listings-and-ads' ) }
				/>
			</Flex>
		</Card>
	);
};

export default CustomerQuotesCard;
