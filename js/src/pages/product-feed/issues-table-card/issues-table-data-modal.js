/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconExternal from 'gridicons/dist/external';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';

/**
 * Renders a modal showing information about a Google Merchant Center issue
 *
 * @fires gla_documentation_link_click with { context: 'issues-data-table-modal' }
 * @param {Object} props React props
 * @param {Object} props.issue The issue to be rendered in the modal
 * @param {Function} [props.onRequestClose] Callback function when closing the modal
 */
const IssuesTableDataModal = ( { issue, onRequestClose = () => {} } ) => {
	return (
		<AppModal
			buttons={ [
				<AppButton
					eventName="gla_documentation_link_click"
					eventProps={ {
						context: 'issues-data-table-modal',
						linkId: issue.code,
						href: issue.action_url,
					} }
					href={ issue.action_url }
					icon={ <GridiconExternal /> }
					key="learn-more"
					target="_blank"
					text={ __( 'Learn more', 'google-listings-and-ads' ) }
					isPrimary
				/>,
			] }
			className="gla-issues-table-data-modal"
			onRequestClose={ onRequestClose }
			title={ issue.issue }
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
