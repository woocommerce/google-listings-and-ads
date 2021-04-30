/**
 * `fallbackBillingUrl` is the billing URL used when `billingStatus.billing_url` is null.
 *
 * `billingStatus.billing_url` can be null when merchants connect an existing ads account.
 *
 * An example scenario:
 *
 * 1. Merchants create a new account, they will get the billing url.
 * 2. For some reasons, they did not complete billing, and they decided to disconnect everything and maybe even uninstall GLA plugin.
 * 3. Then they use GLA again, and they connect the old ads account, the billing url would be null.
 * 4. When the billing url is null, we should use this fallback billing url, so that they can continue billing setup manually.
 */
const fallbackBillingUrl =
	'https://support.google.com/google-ads/answer/2375375';

export default fallbackBillingUrl;
