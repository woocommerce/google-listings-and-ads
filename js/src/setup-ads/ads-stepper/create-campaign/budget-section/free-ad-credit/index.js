/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import GridiconGift from 'gridicons/dist/gift';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import './index.scss';

const FreeAdCredit = () => {
	return (
		<div className="gla-free-ad-credit">
			<GridiconGift />
			<div>
				<div className="gla-free-ad-credit__title">
					{ __(
						'You’re eligible for free ad credit!',
						'google-listings-and-ads'
					) }
				</div>
				<div className="gla-free-ad-credit__description">
					{ createInterpolateElement(
						__(
							'Whatever you spend over the next 30 days will be added back to your Google Ads account. <checklink>Check how much credit you can receive in your country</checklink>. <termslink>Terms and conditions apply</termslink>.',
							'google-listings-and-ads'
						),
						{
							checklink: (
								// TODO: what is the correct link?
								<AppDocumentationLink
									context="setup-ads"
									linkId="check-free-ad-credit-in-country"
									href="https://support.google.com/adspolicy/answer/54818"
								/>
							),
							termslink: (
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
