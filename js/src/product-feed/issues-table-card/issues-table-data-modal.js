/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconExternal from 'gridicons/dist/external';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';

/**
 * Renders a modal showing information about a Google Merchant Center issue
 *
 * @fires gla_documentation_link_click with { context: 'issues-data-table-modal' }
 * @param {Object} params Component params
 * @param {Object} params.issue The issue to be rendered in the modal
 * @param {Function} params.onRequestClose Onnclose callback function
 */
const IssuesTableDataModal = ( { issue, onRequestClose = () => {} } ) => {
	if ( ! issue ) return null;
	return (
		<AppModal
			className="gla-issues-table-data-modal"
			title={ issue.issue }
			onRequestClose={ onRequestClose }
			buttons={ [
				<AppButton
					key="learn-more"
					isPrimary
					target="_blank"
					href={ issue.action_url }
					text={ __( 'Learn more', 'google-listings-and-ads' ) }
					eventName={ 'gla_documentation_link_click' }
					eventProps={ {
						context: 'issues-data-table-modal',
						linkId: issue.code,
						href: issue.action_url,
					} }
					icon={ <GridiconExternal /> }
				/>,
			] }
		>
			<p>
				<strong>
					{ __( 'What to do?', 'google-listings-and-ads' ) }
				</strong>
			</p>
			<p>{ issue.action }</p>
		</AppModal>
	);
};

export default IssuesTableDataModal;
