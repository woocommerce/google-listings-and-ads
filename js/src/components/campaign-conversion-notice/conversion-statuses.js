/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import { LOCAL_STORAGE_KEYS } from '.~/constants';

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
		link: '#',
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
				<Link
					className="gla-campaign-conversion-status-notice-reports__link"
					href={ getNewPath( null, '/google/reports' ) }
				/>
			),
		}
	),
	externalLink: {
		link: '#',
		content: __(
			'Learn more about this upgrade',
			'google-listings-and-ads'
		),
	},
	localStorageKey: LOCAL_STORAGE_KEYS.IS_AFTER_MIGRATION_NOTICE_DISMISSED,
};

const CONVERSION_STATUSES = { AFTER_CONVERSION, BEFORE_CONVERSION };

export default CONVERSION_STATUSES;
