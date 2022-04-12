/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import recordEvent from '.~/utils/recordEvent';

/**
 * Check for whether the phone number for Merchant Center exists or not.
 *
 * @event gla_mc_phone_number_check
 * @property {string} path the path where the check is in.
 * @property {string} exist whether the phone number exists or not.
 * @property {string} isValid whether the phone number is valid or not.
 */

/**
 * @param {import(".~/hooks/useGoogleMCPhoneNumber").PhoneNumberData} phone
 * @fires gla_mc_phone_number_check
 */
const usePhoneNumberCheckTrackEventEffect = ( {
	loaded,
	data: { display, isValid },
} ) => {
	const exist = !! display;
	const path = getPath();

	useEffect( () => {
		if ( ! loaded ) {
			return;
		}

		recordEvent( 'gla_mc_phone_number_check', {
			path,
			exist,
			isValid,
		} );
	}, [ exist, isValid, loaded, path ] );
};

export default usePhoneNumberCheckTrackEventEffect;
