/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getGetStartedUrl, getDashboardUrl } from '.~/utils/urls';

/* global glaTaskData */

/**
 * Use the 'woocommerce_admin_onboarding_task_list' filter to add a task.
 */
addFilter(
	'woocommerce_admin_onboarding_task_list',
	'google-listings-and-ads',
	( tasks ) => {
		return [
			...tasks,
			{
				key: 'gla_complete_setup',
				title: __(
					'Set up Google Listings & Ads',
					'google-listings-and-ads'
				),
				completed: glaTaskData.isComplete,
				onClick: () => {
					// Redirect to the GLA get started or dashboard page.
					const nextUrl = glaTaskData.isComplete
						? getDashboardUrl()
						: getGetStartedUrl();
					getHistory().push( nextUrl );
				},
				visible: true,
				time: __( '20 minutes', 'google-listings-and-ads' ),
				isDismissable: true,
			},
		];
	}
);
