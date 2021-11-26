/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { CardDivider } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import ReclaimUrlCard from '../reclaim-url-card';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppInputLinkControl from '.~/components/app-input-link-control';
import './index.scss';

const SwitchUrlCard = ( props ) => {
	const { id, claimedUrl, newUrl, onSelectAnotherAccount = () => {} } = props;
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const [
		fetchMCAccountSwitchUrl,
		{ loading, error, response },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/switch-url`,
		method: 'POST',
		data: { id },
	} );
	const homeUrl = getSetting( 'homeUrl' );

	const handleSwitch = async () => {
		try {
			await fetchMCAccountSwitchUrl( { parse: false } );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( e.status !== 403 ) {
				const body = await e.json();
				const errorMessage =
					body.message ||
					__(
						'Unable to switch to your new URL. Please try again later.',
						'google-listings-and-ads'
					);
				createNotice( 'error', errorMessage );
			}
		}
	};

	const handleUseDifferentMCClick = () => {
		onSelectAnotherAccount();
	};

	if ( response && response.status === 403 ) {
		return (
			<ReclaimUrlCard
				id={ error.id }
				websiteUrl={ error.website_url }
				onSwitchAccount={ handleUseDifferentMCClick }
			/>
		);
	}

	return (
		<AccountCard
			className="gla-switch-url-card"
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ sprintf(
				// translators: 1: the new URL, 2: account ID.
				__( '%1$s (%2$s)', 'google-listings-and-ads' ),
				newUrl,
				id
			) }
			indicator={
				<AppButton
					isSecondary
					disabled={ loading }
					onClick={ handleUseDifferentMCClick }
				>
					{ __( 'Switch account', 'google-listings-and-ads' ) }
				</AppButton>
			}
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'Switch to this new URL',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<Subsection.Body>
					{ sprintf(
						// translators: %s: claimed URL.
						__(
							'This Merchant Center account already has a verified and claimed URL, %s.',
							'google-listings-and-ads'
						),
						claimedUrl
					) }
				</Subsection.Body>
				<ContentButtonLayout>
					<AppInputLinkControl disabled value={ homeUrl } />
					<AppButton
						isSecondary
						loading={ loading }
						eventName="gla_mc_account_reclaim_url_button_click"
						onClick={ handleSwitch }
					>
						{ __(
							'Switch to this new URL',
							'google-listings-and-ads'
						) }
					</AppButton>
				</ContentButtonLayout>
				<Subsection.HelperText>
					{ sprintf(
						/* translators: 1: new URL. 2: claimed URL. */
						__(
							'If you switch your claimed URL to %1$s, you will lose your claim to %2$s. This will cause any existing product listings tied to %2$s to stop running.',
							'google-listings-and-ads'
						),
						newUrl,
						claimedUrl
					) }
				</Subsection.HelperText>
			</Section.Card.Body>
		</AccountCard>
	);
};

export default SwitchUrlCard;
