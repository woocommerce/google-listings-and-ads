/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import GoogleTagManagerAccountSelectControl from '../google-tag-manager-account-select-control';
import NoticeDetail from '../notice-detail';

/**
 * Renders the account-selection status's detail: the auto-selected single account, or a
 * selector when more than one exists. The "Connect" submit action for whichever is currently
 * picked lives in the sibling `Indicator`, not here — see the incomplete card's own top-level
 * component.
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked account ID.
 * @param {( accountId: string ) => void} props.onAccountChange Callback when the picked account changes.
 * @return {JSX.Element} The detail.
 */
export default function AccountSelection( { accountId, onAccountChange } ) {
	const { account } = useGoogleTagManagerAccount();
	const hasMultipleAccounts = account.accounts.length > 1;

	return (
		<NoticeDetail
			status="info"
			title={
				hasMultipleAccounts
					? __(
							'We found multiple Google Tag Manager accounts',
							'google-listings-and-ads'
					  )
					: __(
							'We found your existing Google Tag Manager account',
							'google-listings-and-ads'
					  )
			}
			body={
				hasMultipleAccounts
					? __( 'Pick one to connect.', 'google-listings-and-ads' )
					: ''
			}
			extraContent={
				<GoogleTagManagerAccountSelectControl
					value={ accountId }
					onChange={ onAccountChange }
				/>
			}
		/>
	);
}
