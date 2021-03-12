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
							'Whatever you spend over the next 30 days will be added back to your Google Ads account. Check how much credit you can receive in your country <checklink>here</checklink>.',
							'google-listings-and-ads'
						),
						{
							checklink: (
								// TODO: need to change this.
								// Upon click, this should upon up a free credit modal, instead of going to an external site.
								<AppDocumentationLink
									context="setup-ads"
									linkId="check-free-ad-credit-in-country"
									href="https://support.google.com/adspolicy/answer/54818"
								/>
							),
						}
					) }
					<br />
					<AppDocumentationLink
						context="setup-ads"
						linkId="free-ad-credit-terms"
						href="https://www.google.com/ads/coupons/terms/"
					>
						{ __(
							'Terms and conditions apply.',
							'google-listings-and-ads'
						) }
					</AppDocumentationLink>
				</div>
			</div>
		</div>
	);
};

export default FreeAdCredit;
