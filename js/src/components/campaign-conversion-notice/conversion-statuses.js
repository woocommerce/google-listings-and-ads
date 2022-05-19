/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS } from '.~/constants';
import { geReportsUrl } from '.~/utils/urls';
import TrackableLink from '.~/components/trackable-link';
import AppDocumentationLink from '.~/components/app-documentation-link';

const BEFORE_CONVERSION = {
	title: __(
		'Your Google Listings & Ads campaigns will soon be automatically upgraded',
		'google-listings-and-ads'
	),
	content: __(
		'From July through September, Google will be upgrading your existing campaigns from Smart Shopping to Performance Max, giving you the same benefits, plus expanded reach. There will be no impact to your spend or campaign settings due to this upgrade.',
		'google-listings-and-ads'
	),
	externalLink: {
		link: 'https://support.google.com/google-ads/answer/11576060',
		linkId: 'campaign-conversion-status-before-migration-read-more',
		content: __(
			'Learn more about this upgrade',
			'google-listings-and-ads'
		),
	},
	localStorageKey: LOCAL_STORAGE_KEYS.IS_BEFORE_MIGRATION_NOTICE_DISMISSED,
};

const AFTER_CONVERSION = {
	title: __(
		'Your Google Listings & Ads campaigns have been automatically upgraded',
		'google-listings-and-ads'
	),
	content: createInterpolateElement(
		__(
			'Google has auto-upgraded your existing campaigns from Smart Shopping to Performance Max, giving you the same benefits plus extended reach across the Google network. No changes were made to your campaign settings and metrics from previous campaigns will continue to be available in <link>Reports</link> for historical purposes.',
			'google-listings-and-ads'
		),
		{
			link: (
				<TrackableLink
					href={ geReportsUrl() }
					className="gla-campaign-conversion-status-notice-reports__link"
					eventName="gla_upgrade_campaign_reports_link_click"
					eventProps={ {
						context: 'dashboard',
						href: geReportsUrl(),
					} }
				/>
			),
		}
	),
	externalLink: {
		link: 'https://support.google.com/google-ads/answer/11576060',
		linkId: 'campaign-conversion-status-after-migration-read-more',
		content: __(
			'Learn more about this upgrade',
			'google-listings-and-ads'
		),
	},
	localStorageKey: LOCAL_STORAGE_KEYS.IS_AFTER_MIGRATION_NOTICE_DISMISSED,
};

const REPORTS_CONVERSION = {
	localStorageKey: LOCAL_STORAGE_KEYS.IS_REPORTS_MIGRATION_NOTICE_DISMISSED,
	content: createInterpolateElement(
		__(
			'Your existing campaigns have been upgraded to Performance Max. <readMoreLink>Learn more about this upgrade</readMoreLink>',
			'google-listings-and-ads'
		),
		{
			readMoreLink: (
				<AppDocumentationLink
					context="reports-programs"
					href="https://support.google.com/google-ads/answer/11576060"
					linkId="campaign-conversion-status-after-migration-reports-read-more"
				/>
			),
		}
	),
};

const CONVERSION_STATUSES = {
	AFTER_CONVERSION,
	BEFORE_CONVERSION,
	REPORTS_CONVERSION,
};

export default CONVERSION_STATUSES;
