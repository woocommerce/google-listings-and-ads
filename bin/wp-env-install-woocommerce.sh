#!/usr/bin/env bash
#
# Proof-of-concept helper for running PHPUnit locally via wp-env.
#
# tests/bootstrap.php needs a full WooCommerce checkout that includes its
# test helper classes (tests/legacy/framework/helpers/*), which only exist
# in the woocommerce/woocommerce GitHub repo -- not in the wordpress.org
# release zip that `wp plugin install woocommerce` (used by
# tests/e2e/bin/test-env-setup.sh for Playwright) pulls down.
#
# This mirrors what bin/install-wp-tests.sh does for CI: a shallow git
# checkout of the plugin, pinned to a version, with its own Composer
# dependencies installed. The result is mounted into wp-env's "tests"
# environment via .wp-env.override.json (see bin/wp-env-generate-override.sh),
# instead of the wordpress.org release build.

set -euo pipefail

WC_VERSION=${1-11.1.0}
WC_SRC_DIR=".wp-env-wc-src"

if [ -d "${WC_SRC_DIR}" ]; then
	CURRENT_VERSION=$(cat "${WC_SRC_DIR}/.wc-version" 2>/dev/null || echo "")
	if [ "${CURRENT_VERSION}" == "${WC_VERSION}" ]; then
		echo "WooCommerce ${WC_VERSION} checkout already present in ${WC_SRC_DIR}."
		exit 0
	fi

	echo "Removing existing ${WC_SRC_DIR} (was pinned to '${CURRENT_VERSION:-unknown}')."
	rm -rf "${WC_SRC_DIR}"
fi

echo "Fetching WooCommerce ${WC_VERSION} from GitHub (plugins/woocommerce only)..."

git clone \
	--quiet \
	--filter=blob:none \
	--no-checkout \
	--depth=1 \
	--branch "${WC_VERSION}" \
	https://github.com/woocommerce/woocommerce.git \
	"${WC_SRC_DIR}"

cd "${WC_SRC_DIR}"
# plugins/woocommerce's composer.json references sibling path-repo packages
# under packages/php/* (blueprint, email-editor, monorepo-plugin), so those
# need to be present in the sparse checkout too.
git sparse-checkout set --cone plugins/woocommerce packages/php
git checkout --quiet
echo "${WC_VERSION}" > .wc-version

echo "Installing WooCommerce's own Composer dependencies (--no-dev)..."
cd plugins/woocommerce
composer install --quiet --no-interaction --no-dev --ignore-platform-reqs

if [ -f bin/generate-feature-config.php ]; then
	echo "Generating WooCommerce feature config..."
	php bin/generate-feature-config.php
fi

echo "Done. WooCommerce ${WC_VERSION} ready at ${WC_SRC_DIR}/plugins/woocommerce"
