/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import recordEvent from '.~/utils/recordEvent';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import ReclaimUrlFailCard from './reclaim-url-fail-card';
import './index.scss';

const ReclaimUrlCard = ( props ) => {
	const { id, websiteUrl } = props;
	const [ checked, setChecked ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const [
		fetchClaimOverwrite,
		{ loading, response, reset },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/claim-overwrite`,
		method: 'POST',
		data: { id },
	} );

	const handleReclaimClick = async () => {
		try {
			await fetchClaimOverwrite( { parse: false } );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( e.status !== 406 ) {
				createNotice(
					'error',
					__(
						'Unable to reclaim your URL. Please try again later.',
						'google-listings-and-ads'
					)
				);
			}
		}
	};

	const handleRetry = () => {
		reset();
	};

	if ( response && response.status === 406 ) {
		return <ReclaimUrlFailCard onRetry={ handleRetry } />;
	}

	const handleCheckboxChange = ( v ) => {
		recordEvent( 'gla_mc_account_reclaim_url_agreement_check', {
			checked: v,
		} );
		setChecked( v );
	};

	return (
		<Section.Card className="gla-reclaim-url-card">
			<Section.Card.Body>
				<Subsection.Title>{ toAccountText( id ) }</Subsection.Title>
				<ContentButtonLayout>
					<div>
						<Subsection.Title>
							{ createInterpolateElement(
								__(
									'Your URL, <url />, is currently claimed by another Merchant Center account. ',
									'google-listings-and-ads'
								),
								{
									url: <span>{ websiteUrl }</span>,
								}
							) }
						</Subsection.Title>
						<Subsection.HelperText className="gla-reclaim-url-card__warning">
							{ createInterpolateElement(
								__(
									'<strong>Warning:</strong> If you reclaim this URL, it will cause any existing product listings or ads to stop running, and any existing shipping or tax configurations will be lost. The other verified account will be notified that they have lost their claim.',
									'google-listings-and-ads'
								),
								{
									strong: <strong />,
								}
							) }
						</Subsection.HelperText>
						<CheckboxControl
							label={ __(
								'Yes, I understand the implications of reclaiming my URL.',
								'google-listings-and-ads'
							) }
							checked={ checked }
							disabled={ loading }
							onChange={ handleCheckboxChange }
						></CheckboxControl>
					</div>
					<AppButton
						isSecondary
						isDestructive
						disabled={ ! checked }
						loading={ loading }
						eventName="gla_mc_account_reclaim_url_button_click"
						onClick={ handleReclaimClick }
					>
						{ __( 'Reclaim my URL', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<AppDocumentationLink
					context="setup-mc"
					linkId="claim-url"
					href="https://support.google.com/merchants/answer/176793"
				>
					{ __(
						'Read more about claiming URLs',
						'google-listings-and-ads'
					) }
				</AppDocumentationLink>
			</Section.Card.Footer>
		</Section.Card>
	);
};

export default ReclaimUrlCard;
