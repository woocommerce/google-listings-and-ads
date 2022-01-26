/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import GridiconGift from 'gridicons/dist/gift';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import './index.scss';
import TrackableLink from '.~/components/trackable-link';
import CountryModal from './country-modal';

const FreeAdCredit = () => {
	const [ showModal, setShowModal ] = useState( false );

	const handleClick = () => {
		setShowModal( true );
	};

	const handleRequestClose = () => {
		setShowModal( false );
	};

	return (
		<div className="gla-free-ad-credit">
			<GridiconGift />
			<div>
				<div className="gla-free-ad-credit__title">
					{ __(
						'You’re eligible for free ad credit!',
						'google-listings-and-ads'
					) }
				</div>
				<div className="gla-free-ad-credit__description">
					{ createInterpolateElement(
						__(
							'Whatever you spend over the next 60 days will be added back to your Google Ads account. Check how much credit you can receive in your country <checklink>here</checklink>.',
							'google-listings-and-ads'
						),
						{
							checklink: (
								<TrackableLink
									eventName="gla_free_ad_credit_country_click"
									eventProps={ {
										context: 'setup-ads',
									} }
									href="#"
									type="wp-admin"
									onClick={ handleClick }
								/>
							),
						}
					) }
					<br />
					<AppDocumentationLink
						context="setup-ads"
						linkId="free-ad-credit-terms"
						href="https://www.google.com/ads/coupons/terms/"
					>
						{ __(
							'Terms and conditions apply.',
							'google-listings-and-ads'
						) }
					</AppDocumentationLink>
				</div>
				{ showModal && (
					<CountryModal onRequestClose={ handleRequestClose } />
				) }
			</div>
		</div>
	);
};

export default FreeAdCredit;
