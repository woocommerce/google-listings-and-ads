/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getGetStartedUrl } from '~/utils/urls';
import { addBaseEventProperties } from '~/utils/tracks';

const GET_STARTED_URL = getGetStartedUrl();
const BASE_EVENT_PROPS = addBaseEventProperties( {} );

const GetStartedCTA = () => {
	return (
		<AppButton
			href={ GET_STARTED_URL }
			eventName="gla_get_started_click"
			eventProps={ BASE_EVENT_PROPS }
			isSecondary
		>
			{ __( 'Get started', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
