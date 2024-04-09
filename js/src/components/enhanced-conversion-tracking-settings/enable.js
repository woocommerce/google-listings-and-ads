/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import EnableButton from '.~/product-feed/submission-success-guide/enhanced-conversions/enable-button';

const Enable = () => {
	return (
		<Fragment>
			<p>
				{ __(
					'In order to enable enhanced conversions, you will need to visit the settings in your Ads account. You will be prompted to accept terms & conditions.',
					'google-listings-and-ads'
				) }
			</p>
			<EnableButton />
		</Fragment>
	);
};

export default Enable;
