/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import './error-card.scss';

/**
 * Clicking on the button to re-verify the Search Console property after verification was lost.
 *
 * @event gla_search_console_action_needed_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the "Action needed" state — shown when Search Console verification is found to have
 * been lost outside of the connect flow (AC-019). Modeled on `RequestFullAccessGoogleAccountCard`'s
 * error-styled description + destructive button template.
 *
 * @fires gla_search_console_action_needed_button_click
 */
const ActionNeededCard = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [ fetchVerify, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/verify`,
		method: 'POST',
	} );

	const handleClick = async () => {
		try {
			await fetchVerify();
			invalidateResolution( 'getSearchConsoleAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to verify your Search Console property. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			alignIcon="top"
			description={
				<p>
					<em>
						{ createInterpolateElement(
							__(
								'<alert>Action needed:</alert> Your Search Console property is no longer verified. Verify it again to keep tracking organic performance.',
								'google-listings-and-ads'
							),
							{
								alert: (
									<span className="gla-search-console-account-card__error-text" />
								),
							}
						) }
					</em>
				</p>
			}
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					isDestructive
					loading={ loading }
					eventName="gla_search_console_action_needed_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ActionNeededCard;
