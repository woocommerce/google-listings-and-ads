/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import { REPORT_SOURCE_FREE, REPORT_SOURCE_PAID } from '.~/constants';

const CONTENT = {
	REPORT_SOURCE_FREE: {
		body: __(
			"We're having trouble loading this data. Try again later, or track your performance in Google Merchant Center.",
			'google-listings-and-ads'
		),
		link: 'https://merchants.google.com/mc/reporting/dashboard',
		eventName: 'gla_google_mc_link_click',
		buttonLabel: __(
			'Open Google Merchant Center',
			'google-listings-and-ads'
		),
	},
	REPORT_SOURCE_PAID: {
		body: __(
			"We're having trouble loading this data. Try again later, or track your performance in Google Ads.",
			'google-listings-and-ads'
		),
		link: 'https://ads.google.com/',
		eventName: 'gla_google_ads_link_click',
		buttonLabel: __( 'Open Google Ads', 'google-listings-and-ads' ),
	},
};
/**
 * Show the notice and link to Google Ads when no data is available
 *
 * @param {string} type The Account type (free|paid)
 * @return {JSX.Element} The Component to be rendered
 */
const CampaignNoData = ( type ) => {
	return (
		<div className="gla-summary-card__body">
			<p>{ CONTENT[ type ].body }</p>
			<AppButton
				eventName={ CONTENT[ type ].eventName }
				eventProps={ {
					context: 'dashboard',
					href: CONTENT[ type ].link,
				} }
				href={ CONTENT[ type ].link }
				target="_blank"
				isSmall
				isSecondary
			>
				{ CONTENT[ type ].buttonLabel }
			</AppButton>
		</div>
	);
};

export default CampaignNoData;
