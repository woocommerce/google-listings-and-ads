/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import countryAmount from './country-amount';
import './index.scss';

const CountryModal = ( props ) => {
	return (
		<AppModal
			className="gla-free-ad-credit-country-modal"
			title={ __(
				'Check your maximum free credit',
				'google-listings-and-ads'
			) }
			{ ...props }
		>
			<p>
				{ __(
					'Whatever you spend in the next month will be added back to your Google Ads account as free credit, up to a maximum limit depending on your store’s country.',
					'google-listings-and-ads'
				) }
			</p>
			<table>
				<tbody>
					{ countryAmount.map( ( el, idx ) => {
						const [ country, amount ] = el;

						return (
							<tr key={ idx }>
								<td>{ country }</td>
								<td>{ amount }</td>
							</tr>
						);
					} ) }
				</tbody>
			</table>
		</AppModal>
	);
};

export default CountryModal;
