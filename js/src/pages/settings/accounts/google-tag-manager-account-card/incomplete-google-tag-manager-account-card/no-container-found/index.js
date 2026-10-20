/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CreateNewContainerButton from '../create-new-container-button';
import ReadOnlyNotice from '../read-only-notice';
import './index.scss';

/**
 * Renders the notice that the account has no container, in place of the selector, with the
 * "Create new container" link. When the connection can't create containers, an explanation is shown
 * in place of the link.
 *
 * @return {JSX.Element} The notice.
 */
export default function NoContainerFound() {
	return (
		<Flex direction="column" gap={ 4 }>
			<span className="gla-google-tag-manager-account-card__no-container-found-label">
				{ __( 'Container', 'google-listings-and-ads' ) }
			</span>
			<p className="gla-google-tag-manager-account-card__no-container-found-text">
				{ __( 'No container found', 'google-listings-and-ads' ) }
			</p>
			<ReadOnlyNotice />
			<CreateNewContainerButton />
		</Flex>
	);
}
