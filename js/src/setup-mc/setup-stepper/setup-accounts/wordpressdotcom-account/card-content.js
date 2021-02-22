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
import useJetpackAccount from '.~/hooks/useJetpackAccount';

const CardContent = () => {
	const { jetpack, isResolving } = useJetpackAccount();

	if ( isResolving ) {
		return <AppSpinner />;
	}

	if ( ! jetpack ) {
		return (
			<TitleButtonLayout
				title={ __(
					'Error while loading WordPress.com account info',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	if ( jetpack.active === 'yes' ) {
		return <TitleButtonLayout title={ jetpack.email } />;
	}

	if ( jetpack.active === 'no' ) {
		return <ConnectAccount />;
	}

	return null;
};

export default CardContent;
