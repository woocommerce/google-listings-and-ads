/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import './data-freshness-notice.scss';

/**
 * How fresh the Search Console data on the Organic search report is.
 *
 * @param {Object} props React props.
 * @param {number|null} [props.lastUpdated] Unix timestamp, in seconds, of the last time the data was
 *   fetched from Search Console. The date line is left out while there is none.
 * @return {JSX.Element} The notice.
 */
const DataFreshnessNotice = ( { lastUpdated = null } ) => {
	return (
		<div className="gla-search-console-data-freshness">
			<p>
				{ __(
					'Search Console data updates every 2 to 3 days.',
					'google-listings-and-ads'
				) }
			</p>
			{ lastUpdated !== null && (
				<p>
					{ sprintf(
						// translators: %s: date the Search Console data was last updated.
						__( 'Last updated %s', 'google-listings-and-ads' ),
						dateI18n(
							glaData.dateFormat,
							new Date( lastUpdated * 1000 )
						)
					) }
				</p>
			) }
		</div>
	);
};

export default DataFreshnessNotice;
