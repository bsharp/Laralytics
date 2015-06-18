'use strict';

var laralytics = {
  init: function(_api, _version) {

    // Object track with : screen size, mouse position, browser, all click event
    var payLoad = {};

    // Options of laralytics when DOM is loaded
    var options = {
      API : _api || '/laralytics',
      Version : _version || null,
    };

    payLoad.push({'Version':options.Version});
    console.log(payLoad);

    laralytics.sendPayload(payLoad, options);
  },

  sendPayload: function(_payLoad, _options) {

    var request = new XMLHttpRequest();
    request.open('POST', ''+ _options.API +'', true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.setRequestHeader('X-XSRF-TOKEN', laralytics.getCookie('XSRF-TOKEN'));
    request.send(_payLoad);
  },

  getCookie: function(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)===' ') c = c.substring(1);
        if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
    }
    return "";
  },
};

laralytics.init();
//# sourceMappingURL=laralytics.js.map