/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import GenAIImagesNoticeGraphic from '~/images/pmax-assets-improvements/gen-ai-images-notice.svg';
import './gen-ai-images-notice.scss';

const GenAIImagesNotice = () => {
	return (
		<Notice
			className="gla-gen-ai-images-notice"
			status="info"
			isDismissible={ false }
		>
			<img
				src={ GenAIImagesNoticeGraphic }
				alt=""
				width="24"
				height="24"
			/>
			{ __(
				"We've used your final URL to auto-populate images…",
				'google-listings-and-ads'
			) }
		</Notice>
	);
};

export default GenAIImagesNotice;
