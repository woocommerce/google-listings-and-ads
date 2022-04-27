/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	__experimentalText as Text,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const CaseStudiesCard = () => {
	return (
		<Card className="gla-get-started-case-studies-card" isBorderless>
			<CardHeader>
				<Text
					className="gla-get-started-case-studies-card__title"
					variant="title.medium"
				>
					{ __(
						'21,000+ WooCommerce store owners like you already list products on Google for free',
						'google-listings-and-ads'
					) }
				</Text>
			</CardHeader>
		</Card>
	);
};

export default CaseStudiesCard;
