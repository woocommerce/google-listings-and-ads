/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import TitleButtonLayout from '../title-button-layout';
import ConnectAccount from './connect-account';
import useGoogleAccount from './useGoogleAccount';

const CardContent = ( props ) => {
	const { disabled } = props;
	const { google, isResolving } = useGoogleAccount();

	if ( isResolving ) {
		return <AppSpinner />;
	}

	if ( ! google ) {
		return (
			<TitleButtonLayout
				title={ __(
					'Error while loading WordPress.com account info',
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
