/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { warning } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useVerifySearchConsoleProperty from '../hooks/useVerifySearchConsoleProperty';
import NoticeDetail from './notice-detail';

/**
 * Clicking on the button to verify the Search Console property, either during the normal
 * verification step or after re-verification is needed following the "action needed" state.
 *
 * @event gla_search_console_verify_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the "action needed" step's detail, shown when Search Console verification is found to
 * have been lost outside of the connect flow.
 *
 * @fires gla_search_console_verify_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function ActionNeededDetail() {
	const { onClick: handleClick, loading } = useVerifySearchConsoleProperty();

	return (
		<NoticeDetail
			status="warning"
			icon={ warning }
			title={ __(
				'Your Search Console property is no longer verified',
				'google-listings-and-ads'
			) }
			body={ __(
				'Verify it again to keep tracking organic performance.',
				'google-listings-and-ads'
			) }
			action={
				<AppButton
					eventName="gla_search_console_verify_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
					loading={ loading }
					isSecondary
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
}
