/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import SearchConsoleSelectControl, {
	CREATE_NEW_PROPERTY_VALUE,
} from './search-console-select-control';
import SearchConsoleNoticeRow from './search-console-notice-row';
import ConnectingRow from './connecting-row';

/**
 * Clicking on the button to select (or create) a Search Console property.
 *
 * @event gla_search_console_property_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the property-selection step: while the backend is still resolving a single-match or
 * no-match property, the "connecting" row is shown; once multiple candidate properties are
 * reported, a selector (with a "Create new" option) lets the merchant choose which one to
 * connect.
 *
 * @fires gla_search_console_property_select_button_click
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The property-selection row.
 */
export default function PropertySelectionRow( { account } ) {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const { searchConsoleAccount, hasFinishedResolution } =
		useSearchConsoleAccount();
	const properties = searchConsoleAccount?.properties ?? [];
	const [ value, setValue ] = useState();

	const [ fetchSelectProperty, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/property`,
		method: 'POST',
	} );

	const handleSelectClick = async () => {
		try {
			const isCreatingNew = value === CREATE_NEW_PROPERTY_VALUE;

			await fetchSelectProperty( {
				data: isCreatingNew ? { create_new: true } : { url: value },
			} );
			invalidateResolution( 'getSearchConsoleAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to select your Search Console property. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	// Single-match or no-match: the backend has already resolved the property silently —
	// no prompt is shown.
	if ( ! hasFinishedResolution || properties.length <= 1 ) {
		return <ConnectingRow account={ account } />;
	}

	// Multi-match: show the selector, with non-covering properties greyed out and
	// explained, plus the "Create new" option.
	return (
		<SearchConsoleNoticeRow
			account={ account }
			status="info"
			icon={ info }
			badgeLabel={ __( 'In progress', 'google-listings-and-ads' ) }
			title={ __(
				'We found multiple Google Search Console properties',
				'google-listings-and-ads'
			) }
			body={ __( 'Pick one to connect.', 'google-listings-and-ads' ) }
			detail={
				<SearchConsoleSelectControl
					value={ value }
					onChange={ setValue }
				/>
			}
			action={
				<AppButton
					eventName="gla_search_console_property_select_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleSelectClick }
					disabled={ ! value }
					loading={ loading }
					isSecondary
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
}
