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
    'util/url',
    'layout/loading-bar'
], function ($, urlUtil, loadingBar) {
    'use strict';

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
            var $container = $('.delegated-view');
            var delivery = $container.data('delivery');
            var defaultTag = $container.data('defaultTag');
            var action = $container.data('action');
            var controller = $container.data('controller');
            var extension = $container.data('extension');
            var page = urlUtil.route(action, controller, extension);
            var params = {
                delivery: delivery,
                defaultTag: defaultTag,
            };

            $container.load(page, params, function () {
                loadingBar.stop();
            });
        }
    };

    // the page is always loading data when starting
    loadingBar.start();

    return delegateCtlr;
});
