/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import HelpPopover from '../help-popover';
import './index.scss';

const ProductStatusHelpPopover = () => {
	const map = {
		strong: <strong></strong>,
	};

	return (
		<div className="gla-product-status-help-popover">
			<HelpPopover>
				<p>
					{ createInterpolateElement(
						__(
							'Your product feed is <strong>automatically synced</strong> from WooCommerce to Google, every 1-2 days. ',
							'google-listings-and-ads'
						),
						map
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<strong>‘Not synced’ products</strong> do not appear in Google listings. They are queued for submission, or they may be ineligible or excluded from the product feed.',
							'google-listings-and-ads'
						),
						map
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'After submission, Google assigns each product a status: <strong>Active, Partially Active, Expiring, Pending, or Disapproved.</strong>',
							'google-listings-and-ads'
						),
						map
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<strong>‘Active’ products</strong> are fully approved and eligible to appear in standard and enhanced listings on Google.',
							'google-listings-and-ads'
						),
						map
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<strong>‘Partially active’ products</strong> are fully approved and eligible to appear in standard listings only.',
							'google-listings-and-ads'
						),
						map
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<strong>‘Expiring’ products</strong> will become inactive and no longer appear in Google listings in the next 3 days.',
							'google-listings-and-ads'
						),
						map
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<strong>‘Pending’ products</strong> are being processed by Google. They will not appear in listings until they are approved.',
							'google-listings-and-ads'
						),
						map
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<strong>‘Disapproved’ products</strong> are inactive and do not appear in Google listings.',
							'google-listings-and-ads'
						),
						map
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'<link>Read more about product sync and statuses</link>',
							'google-listings-and-ads'
						),
						{
							link: (
								<Link
									type="external"
									target="_blank"
									href="https://support.google.com/merchants/answer/160491"
								></Link>
							),
						}
					) }
				</p>
			</HelpPopover>
		</div>
	);
};

export default ProductStatusHelpPopover;
