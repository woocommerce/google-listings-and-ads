/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AppTextButton from '.~/components/app-text-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '.~/components/content-button-layout';
import AccountId from '.~/components/account-id';
import './index.scss';

const OverwriteFeedCard = ( props ) => {
	const { id, url, onSelectAnotherAccount = () => {} } = props;
	const { receiveMCAccount } = useAppDispatch();
	const [ fetchMCAccountOverwriteFeed, { loading } ] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts/feed-overwrite`,
		method: 'POST',
		data: { id },
	} );

	const handleOverwriteFeed = async () => {
		const account = await fetchMCAccountOverwriteFeed();

		receiveMCAccount( account );
	};

	return (
		<Section.Card className="gla-overwrite-feed-card">
			<Section.Card.Body>
				<ContentButtonLayout>
					<Subsection.Title>
						<AccountId id={ id } />
					</Subsection.Title>
				</ContentButtonLayout>
				<ContentButtonLayout>
					<div>
						<Subsection.Title>
							{ createInterpolateElement(
								__(
									'Your URL, <url />, has an existing product feed in Google Merchant Center.',
									'google-listings-and-ads'
								),
								{
									url: <span>{ url }</span>,
								}
							) }
						</Subsection.Title>
						<Subsection.HelperText>
							{ __(
								'If you click ‘Overwrite my feed’ and complete the setup, your existing product feed and settings will be overwritten by this WooCommerce integration.',
								'google-listings-and-ads'
							) }
						</Subsection.HelperText>
					</div>
					<div className="buttons">
						<AppButton
							isSecondary
							loading={ loading }
							onClick={ handleOverwriteFeed }
						>
							{ __(
								'Overwrite my feed',
								'google-listings-and-ads'
							) }
						</AppButton>
					</div>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<AppTextButton isSecondary onClick={ onSelectAnotherAccount }>
					{ __(
						'Or, use a different Merchant Center account',
						'google-listings-and-ads'
					) }
				</AppTextButton>
			</Section.Card.Footer>
		</Section.Card>
	);
};

export default OverwriteFeedCard;
