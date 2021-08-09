/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import recordEvent from '.~/utils/recordEvent';

const usePhoneNumberCheckTrackEventEffect = ( view, phone ) => {
	const {
		loaded,
		data: { display, isValid },
	} = phone;
	const exist = !! display;

	useEffect( () => {
		if ( ! loaded ) {
			return;
		}

		recordEvent( 'gla_mc_phone_number_check', {
			view,
			exist,
			isValid,
		} );
	}, [ exist, isValid, loaded, view ] );
};

export default usePhoneNumberCheckTrackEventEffect;
