/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import TitleButtonLayout from '.~/components/title-button-layout';
import ConnectAccount from './connect-account';

const CardContent = ( props ) => {
	const { disabled } = props;
	const { google, isResolving } = useGoogleAccount();

	if ( isResolving ) {
		return <AppSpinner />;
	}

	if ( disabled ) {
		return <ConnectAccount disabled={ disabled } />;
	}

	if ( ! google ) {
		return (
			<TitleButtonLayout
				title={ __(
					'Error while loading Google account info',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	if ( google.active === 'yes' ) {
		return <TitleButtonLayout title={ google.email } />;
	}

	return <ConnectAccount disabled={ disabled } />;
};

export default CardContent;
