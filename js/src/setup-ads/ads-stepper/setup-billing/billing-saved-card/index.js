/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconCreditCard from 'gridicons/dist/credit-card';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AppSpinner from '.~/components/app-spinner';
import TitleButtonLayout from '.~/components/title-button-layout';
import TrackableLink from '.~/components/trackable-link';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Section from '.~/wcdl/section';
import './index.scss';

/**
 * Clicking on a Google Ads account text link.
 *
 * @event gla_google_ads_account_link_click
 * @property {string} context indicate which page / module the link is in
 * @property {string} href where the user is redirected
 * @property {string} link_id a unique ID for the link within the page / module
 */

/**
 * @fires gla_google_ads_account_link_click with `{ context: 'setup-ads', link_id: 'google-ads-account' }`
 */
const BillingSavedCard = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();

	if ( ! googleAdsAccount ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-google-ads-billing-saved-card">
			<Section.Card>
				<Section.Card.Body>
					<div className="gla-google-ads-billing-saved-card__account-number">
						<TitleButtonLayout
							title={ toAccountText( googleAdsAccount.id ) }
						/>
					</div>
					<div className="gla-google-ads-billing-saved-card__description">
						<GridiconCreditCard />
						<div>
							{ createInterpolateElement(
								__(
									'Great! You already have billing information saved for this <link>Google Ads account</link>.',
									'google-listings-and-ads'
								),
								{
									link: (
										<TrackableLink
											eventName="gla_google_ads_account_link_click"
											eventProps={ {
												context: 'setup-ads',
												link_id: 'google-ads-account',
												href:
													'https://ads.google.com/aw/overview ',
											} }
											type="external"
											target="_blank"
											href="https://ads.google.com/aw/overview"
										/>
									),
								}
							) }
						</div>
					</div>
				</Section.Card.Body>
			</Section.Card>
		</div>
	);
};

export default BillingSavedCard;
