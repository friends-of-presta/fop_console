/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

const path = require('path');
const webpack = require('webpack');
const keepLicense = require('uglify-save-license');

const psRootDir = path.resolve(process.env.PWD, '../../../');
const psJsDir = path.resolve(psRootDir, 'admin-dev/themes/new-theme/js');
const psAppDir = path.resolve(psJsDir, 'app');
const psComponentsDir = path.resolve(psJsDir, 'components');

const config = {
    entry: {
        grid: [
            './js/grid',
        ],
        form: [
            './js/form',
        ]
    },
    output: {
        path: path.resolve(__dirname, 'public'),
        filename: '[name].bundle.js'
    },
    //devtool: 'source-map', // uncomment me to build source maps (really slow)
    resolve: {
        extensions: ['.js'],
        alias: {
            '@app': psAppDir,
            '@components': psComponentsDir,
        },
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                include: path.resolve(__dirname, 'js'),
                use: [{
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            ['es2015', { modules: false }]
                        ]
                    }
                }]
            },
            {
                test: /\.js$/,
                include: path.resolve(__dirname, '../../../admin-dev/themes/new-theme/js'),
                use: [{
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            ['es2015', { modules: false }]
                        ]
                    }
                }]
            }
        ]
    },
    plugins: []
};

if (process.env.NODE_ENV === 'production') {
    config.plugins.push(
        new webpack.optimize.UglifyJsPlugin({
            sourceMap: false,
            compress: {
                sequences: true,
                conditionals: true,
                booleans: true,
                if_return: true,
                join_vars: true,
                drop_console: true
            },
            output: {
                comments: keepLicense
            }
        })
    );
} else {
    config.plugins.push(new webpack.HotModuleReplacementPlugin());
}

module.exports = config;
