/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { CardDivider, Notice } from '@wordpress/components';
import { noop } from 'lodash';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import './index.scss';
import AppInputLinkControl from '.~/components/app-input-link-control';

/**
 * Clicking on the button to reclaim URL for a Google Merchant Center account.
 *
 * @event gla_mc_account_reclaim_url_button_click
 */

/**
 * @param {Object} props React props
 * @param {string} props.id
 * @param {string} props.websiteUrl
 * @param {Function} [props.onSwitchAccount]
 * @fires gla_mc_account_reclaim_url_button_click
 */
const ReclaimUrlCard = ( { id, websiteUrl, onSwitchAccount = noop } ) => {
	const { invalidateResolution } = useAppDispatch();
	const [
		fetchClaimOverwrite,
		{ loading, error, reset },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/claim-overwrite`,
		method: 'POST',
		data: { id },
	} );
	const homeUrl = getSetting( 'homeUrl' );

	const handleReclaimClick = async () => {
		reset();
		await fetchClaimOverwrite( { parse: false } );
		invalidateResolution( 'getGoogleMCAccount', [] );
	};

	return (
		<AccountCard
			className="gla-reclaim-url-card"
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ sprintf(
				// translators: 1: website URL, 2: account ID.
				__( '%1$s (%2$s)', 'google-listings-and-ads' ),
				websiteUrl,
				id
			) }
			indicator={
				<AppButton
					isSecondary
					disabled={ loading }
					eventName="gla_mc_account_switch_account_button_click"
					eventProps={ {
						context: 'reclaim-url',
					} }
					onClick={ onSwitchAccount }
				>
					{ __( 'Switch account', 'google-listings-and-ads' ) }
				</AppButton>
			}
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __( 'Reclaim your URL', 'google-listings-and-ads' ) }
				</Subsection.Title>
				<Subsection.Body>
					{ __(
						'Your URL is currently claimed by another Merchant Center account.',
						'google-listings-and-ads'
					) }
				</Subsection.Body>
				<ContentButtonLayout>
					<AppInputLinkControl disabled value={ homeUrl } />
					<AppButton
						isSecondary
						loading={ loading }
						eventName="gla_mc_account_reclaim_url_button_click"
						onClick={ handleReclaimClick }
					>
						{ __( 'Reclaim my URL', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
				<Subsection.HelperText>
					{ createInterpolateElement(
						__(
							'If you reclaim this URL, it will cause any existing product listings or ads to stop running, and the other verified account will be notified that they have lost their claim. <link>Learn more</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="setup-mc"
									linkId="claim-url"
									href="https://support.google.com/merchants/answer/176793"
								/>
							),
						}
					) }
				</Subsection.HelperText>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ createInterpolateElement(
							__(
								'<strong>We were unable to reclaim this URL.</strong> You may not have permission to reclaim this URL, or an error might have occurred. Try again later or contact your Google account administrator.',
								'google-listings-and-ads'
							),
							{
								strong: <strong></strong>,
							}
						) }
					</Notice>
				) }
			</Section.Card.Body>
		</AccountCard>
	);
};

export default ReclaimUrlCard;
