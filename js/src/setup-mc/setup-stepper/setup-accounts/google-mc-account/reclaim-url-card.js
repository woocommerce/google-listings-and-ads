/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import ContentButtonLayout from '../content-button-layout';
import Subsection from '.~/wcdl/subsection';
import AppButton from '.~/components/app-button';
import AccountId from './account-id';

const ReclaimUrlCard = () => {
	// TODO:
	const accountId = 123123123;
	const url = 'http://www.colleenscookies.biz';

	// TODO: add reclaim handler.
	const handleReclaimClick = () => {};

	return (
		<Section.Card>
			<Section.Card.Body>
				<Subsection.Title>
					<AccountId id={ accountId } />
				</Subsection.Title>
				<ContentButtonLayout>
					<div>
						<Subsection.Title>
							{ createInterpolateElement(
								__(
									'Your URL, <url />, is currently claimed by another Merchant Center account. ',
									'google-listings-and-ads'
								),
								{
									url: <span>{ url }</span>,
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
					<AppButton isSecondary onClick={ handleReclaimClick }>
						{ __( 'Reclaim my URL', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ReclaimUrlCard;
