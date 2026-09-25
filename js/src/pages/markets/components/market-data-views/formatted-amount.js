/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useMarketCurrency from '../../hooks/useMarketCurrency';

/**
 * Renders a currency-formatted monetary amount.
 *
 * For single-language stores every amount is in the Ads account currency, so
 * `currencyCode` is ignored and `useAdsCurrency` drives formatting. For
 * multilingual stores `currencyCode` is passed to `useMarketCurrency`, which
 * formats the value in the market's own currency.
 *
 * @param {Object} props
 * @param {number} props.amount Numeric amount to format.
 * @param {string} [props.currencyCode] ISO 4217 currency code. Used only on multilingual stores.
 * @return {string} Localised currency string.
 */
const FormattedAmount = ( { amount, currencyCode } ) => {
	const { formatAmount: adsFormatAmount } = useAdsCurrency();
	const { formatAmount: marketFormatAmount } = useMarketCurrency();

	// For non-multilingual stores every amount is expressed in the single Ads
	// account currency, so we use formatAmount from useAdsCurrency directly.
	// For multilingual stores each market may carry its own currency, so we
	// delegate to useMarketCurrency which accepts a per-call currency code.
	const formatAmount = glaData.isMultiLingualStore
		? ( value, code ) => marketFormatAmount( value, code )
		: ( value ) => adsFormatAmount( value );

	return formatAmount( amount, currencyCode );
};

export default FormattedAmount;
