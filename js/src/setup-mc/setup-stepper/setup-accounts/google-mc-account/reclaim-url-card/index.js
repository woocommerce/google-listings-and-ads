/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import ReclaimUrlFailCard from './reclaim-url-fail-card';

const ReclaimUrlCard = ( props ) => {
	const { websiteUrl } = props;
	const { createNotice } = useDispatchCoreNotices();
	const { receiveMCAccount } = useAppDispatch();
	const [
		fetchClaimOverwrite,
		{ loading, response, reset },
	] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/claim-overwrite`,
		method: 'POST',
	} );

	const handleReclaimClick = async () => {
		try {
			const res = await fetchClaimOverwrite( { parse: false } );
			const data = await res.json();

			receiveMCAccount( data );
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

	return (
		<Section.Card>
			<Section.Card.Body>
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
						<Subsection.HelperText>
							{ __(
								'If you reclaim this URL, it will cause any existing product listings or ads to stop running, and the other verified account will be notified that they have lost their claim.',
								'google-listings-and-ads'
							) }
						</Subsection.HelperText>
					</div>
					<AppButton
						isSecondary
						loading={ loading }
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
