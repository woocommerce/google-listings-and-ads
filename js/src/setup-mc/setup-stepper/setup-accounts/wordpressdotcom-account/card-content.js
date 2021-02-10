/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import AppSpinner from '../../../../components/app-spinner';
import { STORE_KEY } from '../../../../data/constants';
import TitleButtonLayout from '../title-button-layout';
import ConnectAccount from './connect-account';

const CardContent = () => {
	const { jetpack, isResolving } = useSelect( ( select ) => {
		const acc = select( STORE_KEY ).getJetpackAccount();
		const resolving = select( STORE_KEY ).isResolving(
			'getJetpackAccount'
		);

		return { jetpack: acc, isResolving: resolving };
	} );

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
