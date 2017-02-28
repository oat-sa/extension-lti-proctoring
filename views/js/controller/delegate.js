/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
define([
    'jquery',
    'controller/app',
    'util/url',
    'ui/container',
    'layout/loading-bar'
], function ($, appController, urlUtil, containerFactory, loadingBar) {
    'use strict';

    /**
     * The CSS scope
     * @type {String}
     */
    var cssScope = '.delegated-view';

    /**
     * Delegate the UI to another controller through an AJAX request
     *
     * @type {Object}
     */
    var delegateCtlr = {
        /**
         * Entry point of the page
         */
        start: function start() {
            var container, action, controller, extension, params;

            container = containerFactory('.container').changeScope(cssScope);
            action = container.getValue('action');
            controller = container.getValue('controller');
            extension = container.getValue('extension');
            params = {
                defaulttag: container.getValue('defaulttag')
            };
            if (container.hasValue('delivery')) {
                params.delivery = container.getValue('delivery');
            }
            container.removeData();
            container.destroy();

            appController.getRouter().forward(urlUtil.route(action, controller, extension, params));
        }
    };

    // the page is always loading data when starting
    loadingBar.start();

    return delegateCtlr;
});
