/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import recordEvent from '.~/utils/recordEvent';

const usePhoneNumberCheckTrackEventEffect = ( phone ) => {
	const {
		loaded,
		data: { display, isValid },
	} = phone;
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
