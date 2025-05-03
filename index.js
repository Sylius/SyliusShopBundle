/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

const path = require('path');
const Encore = require('@symfony/webpack-encore');

class SyliusShop {
    static getWebpackConfig(rootDir) {
        this._prepareWebpackConfig(rootDir);
        Encore
            .addEntry('shop-entry', path.resolve(__dirname, 'Resources/assets/entrypoint.js'))

        const shopConfig = Encore.getWebpackConfig();

        shopConfig.externals = { ...shopConfig.externals, window: 'window', document: 'document' };
        shopConfig.name = 'shop';

        Encore.reset();

        return shopConfig;
    }

    static _getInternalWebpackConfig(rootDir) {
        this._prepareWebpackConfig(rootDir);
        // For a ready-to-use Stimulus bridge. Should be used only for sylius/sylius tests
        Encore
            .addEntry('shop-entry', path.resolve(__dirname, 'Resources/assets/app.js'))
            .enableStimulusBridge(path.resolve(__dirname, 'Resources/assets/controllers.json'));
        const shopConfig = Encore.getWebpackConfig();

        shopConfig.externals = { ...shopConfig.externals, window: 'window', document: 'document' };
        shopConfig.name = 'shop';

        Encore.reset();

        return shopConfig;
    }

    static _prepareWebpackConfig(rootDir) {
        Encore
            .setOutputPath('public/build/shop/')
            .setPublicPath('/build/shop')
            .disableSingleRuntimeChunk()
            .cleanupOutputBeforeBuild()
            .enableSourceMaps(!Encore.isProduction())
            .enableVersioning(Encore.isProduction())
            .enableSassLoader((options) => {
                // eslint-disable-next-line no-param-reassign
                options.additionalData = `$rootDir: '${rootDir}';`;
            })
    }
}

module.exports = SyliusShop;
