/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaBusinessProfileData } from '~/constants';
import BusinessProfileShareMetaBox from './business-profile-share-meta-box';

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.querySelector(
		'#gla-business-profile-share-box'
	);
	const post = glaBusinessProfileData?.businessProfilePost;

	if ( ! container || ! post ) {
		return;
	}

	createRoot( container ).render(
		<BusinessProfileShareMetaBox
			post={ post }
			shareFieldName={ container.dataset.shareFieldName }
			summaryFieldName={ container.dataset.summaryFieldName }
		/>
	);
} );
