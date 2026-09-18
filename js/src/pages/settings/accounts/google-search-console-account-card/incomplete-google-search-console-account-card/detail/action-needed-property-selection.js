/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PropertySelection from './property-selection';

/**
 * Builds the action-needed notice, wording the body differently depending on whether another
 * property is available to switch to.
 *
 * @param {boolean} hasCandidates Whether another property is available to select.
 * @return {{status: 'warning', title: string, body: string}} The notice content.
 */
export function actionNeededNotice( hasCandidates ) {
	return {
		status: 'warning',
		title: __(
			'Your Search Console property needs attention',
			'google-listings-and-ads'
		),
		body: hasCandidates
			? __(
					'There is an issue with the connected property. It may have been deleted, or the connected account may no longer have verified access to it. Select another property below, or create a new one.',
					'google-listings-and-ads'
			  )
			: __(
					'There is an issue with the connected property. It may have been deleted, or the connected account may no longer have verified access to it. Create a new property to reconnect.',
					'google-listings-and-ads'
			  ),
	};
}

/**
 * Renders the action-needed step's detail: {@see ./property-selection.js}'s selector and
 * create-new action, with copy explaining that the previously connected property is no longer
 * usable rather than the initial multi-match copy. Unlike the initial setup step, the create
 * action stays available even with zero other candidates.
 *
 * @return {JSX.Element} The detail.
 */
export default function ActionNeededPropertySelection() {
	return (
		<PropertySelection
			notice={ actionNeededNotice }
			alwaysShowCreateAction
		/>
	);
}
