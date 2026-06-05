/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

const ProductIssues = ( { triggeredAt, onDismiss } ) => {
	return (
		<Notification
			title={ __(
				'Resolve product sync errors',
				'google-listings-and-ads'
			) }
			description={ __(
				'Some of your products are not visible on Google due to configuration issues. Review and fix these errors to ensure your full inventory is operational and can start appearing across Google.',
				'google-listings-and-ads'
			) }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/product-feed',
					children: __(
						'View Product Issues',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default ProductIssues;
