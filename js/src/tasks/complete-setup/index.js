/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { getHistory, getNewPath } from '@woocommerce/navigation';

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
					'Setup Google Listings & Ads',
					'google-listings-and-ads'
				),
				completed: glaTaskData.isComplete,
				onClick: () => {
					// Redirect to the GLA get started page.
					getHistory().push( getNewPath( {}, '/google/start' ) );
				},
				visible: true,
				time: __( '30 minutes', 'google-listings-and-ads' ),
				isDismissable: true,
			},
		];
	}
);
