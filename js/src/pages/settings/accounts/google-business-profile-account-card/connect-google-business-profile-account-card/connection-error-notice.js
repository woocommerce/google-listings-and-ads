/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { ERROR_SLOTS } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import NoticeDetail from '../notice-detail';

/**
 * The connection error slot(s) this notice reads from and clears.
 *
 * @type {Array<string>}
 */
export const CONNECTION_ERROR_SLOTS = [
	ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
];

/**
 * Renders the connection-error notice shown in the `AccountCard` error area when connecting a
 * Google Business Profile location failed. "Try again" clears the error slot itself.
 *
 * `null` when the store has no error for the connection error slot.
 *
 * @return {JSX.Element|null} The notice, or `null` when there's no connection error.
 */
export default function ConnectionErrorNotice() {
	const [ detailedError ] = useDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
	const { clearDetailedErrorBySlots } = useAppDispatch();

	if ( ! detailedError ) {
		return null;
	}

	const apiMessage = detailedError.error?.message;

	const handleTryAgainClick = () => {
		clearDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
	};

	return (
		<NoticeDetail
			status="error"
			title={ __(
				"We couldn't connect Google Business Profile",
				'google-listings-and-ads'
			) }
			body={
				<p>
					{ apiMessage ||
						__(
							"Something went wrong. Check that you're signed in to the right Google account, then try again.",
							'google-listings-and-ads'
						) }
				</p>
			}
			actions={ [
				<AppButton
					key="try-again"
					onClick={ handleTryAgainClick }
					isSecondary
				>
					{ __( 'Try again', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}
