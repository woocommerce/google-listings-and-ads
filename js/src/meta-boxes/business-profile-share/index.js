/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import BusinessProfileShareMetaBox from './business-profile-share-meta-box';

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.querySelector(
		'#gla-business-profile-share-box'
	);
	const location = glaData?.businessProfileLocation;
	const post = glaData?.businessProfilePost;

	if ( ! container || ! location || ! post ) {
		return;
	}

	createRoot( container ).render(
		<BusinessProfileShareMetaBox
			location={ location }
			post={ post }
			shareFieldName={ container.dataset.shareFieldName }
			summaryFieldName={ container.dataset.summaryFieldName }
		/>
	);
} );
