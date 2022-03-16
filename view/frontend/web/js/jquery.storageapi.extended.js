define([
    'jquery',
    'jquery/jquery.cookie',
    'jquery/jquery.storageapi.min',
    'mage/cookies',
    'Magento_Cookie/js/jquery.storageapi.extended',
], function ($) {
    'use strict';

    function _extend(storage) {
        var origSetConf = storage.setConf.bind(storage);
        var origSetItem = storage.setItem.bind(storage);

        storage.setItem = function (name, value, options) {
            options = options || {};
            options.samesite = 'None';
            //
            return origSetItem(name, value, options);
        };

        storage.setConf =  function (c) {
            c = c || {};
            c.samesite = 'None';
            //
            return origSetConf(c);
        };
    };

    if (window.cookieStorage) {
        _extend(window.cookieStorage);
    }

    if ($.mage && $.mage.cookies) {
        var origSet = $.mage.cookies.set.bind($.mage.cookies);

        $.extend($.mage.cookies, {
            set: function (name, value, options) {
                options = options || {};
                options.samesite = 'None';
                //
                return origSet(name, value, options);
            }
        });
    }
});
