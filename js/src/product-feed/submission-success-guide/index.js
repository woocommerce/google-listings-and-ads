/**
 * External dependencies
 */
import { getHistory, getNewPath, getQuery } from '@woocommerce/navigation';
import { createInterpolateElement, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Guide } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ReactComponent as GoogleLogoSvg } from './google-logo.svg';
import { ReactComponent as WooCommerceLogoSvg } from './woocommerce-logo.svg';
import GuidePageContent, {
	ContentLink,
} from '.~/components/guide-page-content';
import './index.scss';

const GUIDE_NAME = 'submission-success';
const CONFIRM_BUTTON_CLASS = 'components-guide__finish-button';

const image = (
	<div className="gla-submission-success-guide__logo-block">
		<div className="gla-submission-success-guide__logo-item">
			<WooCommerceLogoSvg viewBox="0 0 145 31" />
		</div>
		<div className="gla-submission-success-guide__logo-separator-line" />
		<div className="gla-submission-success-guide__logo-item">
			<GoogleLogoSvg width="106" height="36" viewBox="0 0 272 92" />
		</div>
	</div>
);

const pages = [
	{
		image,
		content: (
			<GuidePageContent
				title={ __(
					'You have successfully set up Google Listings & Ads!',
					'google-listings-and-ads'
				) }
			>
				<p>
					{ __(
						'You can review and edit your product feed at any time on this page. We will also notify you of any product feed issues to ensure your products get approved and perform well on Google.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ __(
						'Google reviews product listings in 3-5 days. If approved, your products will automatically be live and searchable on Google.',
						'google-listings-and-ads'
					) }
				</p>
			</GuidePageContent>
		),
	},
	{
		image,
		content: (
			<GuidePageContent
				title={ __(
					`You've been automatically enrolled in Google's Free Listings program.`,
					'google-listings-and-ads'
				) }
			>
				{ createInterpolateElement(
					__(
						'Once approved, your products will be listed as part of a free program, <freeListingsLink>Google Free Listings</freeListingsLink>. You can opt out of this program in <merchantCenterLink>Google Merchant Center</merchantCenterLink>.',
						'google-listings-and-ads'
					),
					{
						// TODO: The free listings link will be added when its URL is ready
						freeListingsLink: (
							<ContentLink
								href="/"
								context="setup-mc-free-listings"
							/>
						),
						merchantCenterLink: (
							<ContentLink
								href="https://www.google.com/retail/solutions/merchant-center/"
								context="setup-mc-merchant-center"
							/>
						),
					}
				) }
			</GuidePageContent>
		),
	},
];

// TODO: The current close method is temporarily for demo.
//       Need to reconsider how this guide modal would be triggered later.
const handleGuideFinish = ( e ) => {
	const nextQuery = {
		...getQuery(),
		guide: undefined,
	};
	const path = getNewPath( nextQuery );
	getHistory().replace( path );

	// Since there is no built-in way to distinguish the modal/guide is closed by what action,
	// here is a workaround by identifying the close button's class name.
	const target = e.currentTarget || e.target;
	const action = target.classList.contains( CONFIRM_BUTTON_CLASS )
		? 'confirm'
		: 'dismiss';
	recordEvent( 'gla_modal_closed', {
		context: GUIDE_NAME,
		action,
	} );
};

const GuideImplementation = () => {
	useEffect( () => {
		recordEvent( 'gla_modal_open', { context: GUIDE_NAME } );
	}, [] );

	return (
		<Guide
			className="gla-submission-success-guide"
			finishButtonText={ __( 'Got it', 'google-listings-and-ads' ) }
			pages={ pages }
			onFinish={ handleGuideFinish }
		/>
	);
};

/**
 * Modal window to greet the user at Product Feed, after successful completion of onboarding.
 *
 * Show this guide modal by visiting the path with a specific query `guide=submission-success`.
 * For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed&guide=submission-success`.
 *
 * TODO: The current open method is temporarily for demo.
 *       Need to reconsider how this guide modal would be triggered later.
 */
export default function SubmissionSuccessGuide() {
	const isOpen = getQuery().guide === GUIDE_NAME;

	if ( ! isOpen ) {
		return null;
	}
	return <GuideImplementation />;
}
