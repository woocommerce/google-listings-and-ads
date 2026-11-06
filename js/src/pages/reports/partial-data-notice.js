/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Shows a warning {@link Notice} when the Google Ads report data is partial,
 * so merchants don't read incomplete numbers as the full picture.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.truncated=false] Whether the report data is partial.
 * @return {JSX.Element|null} The notice, or `null` when the data is complete.
 */
const PartialDataNotice = ( { truncated = false } ) => {
	if ( ! truncated ) {
		return null;
	}

	return (
		<Notice
			className="gla-reports-partial-data-notice"
			status="warning"
			isDismissible={ false }
		>
			{ __(
				'Some Google Ads data for this date range couldn’t be loaded because the report is too large, so parts of this page may be incomplete.',
				'google-listings-and-ads'
			) }
		</Notice>
	);
};

export default PartialDataNotice;
