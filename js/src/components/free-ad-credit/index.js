/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import GridiconGift from 'gridicons/dist/gift';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import './index.scss';

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-ads', link_id: 'free-ad-credit-terms', href: 'https://www.google.com/ads/coupons/terms/' }`
 */
const FreeAdCredit = () => {
	return (
		<div className="gla-free-ad-credit">
			<GridiconGift />
			<div>
				<div className="gla-free-ad-credit__title">
					{ __(
						'Spend $500 to get $500 in Google Ads credits!',
						'google-listings-and-ads'
					) }
				</div>
				<div className="gla-free-ad-credit__description">
					{ createInterpolateElement(
						__(
							'New to Google Ads? Get $500 in ad credit when you spend $500 within your first 60 days. <termLink>Terms and conditions apply</termLink>.',
							'google-listings-and-ads'
						),
						{
							termLink: (
								<AppDocumentationLink
									context="setup-ads"
									linkId="free-ad-credit-terms"
									href="https://www.google.com/ads/coupons/terms/"
								/>
							),
						}
					) }
				</div>
			</div>
		</div>
	);
};

export default FreeAdCredit;
