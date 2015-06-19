'use strict';

(function() {
  var param = {

  };
}();

var laralytics = {

  init: function() {

    // Object track with : screen size, mouse position, browser, all click event
    var payLoad = {};

    // Options of laralytics when DOM is loaded
    var options = {
      API : '/laralytics',
      Version : null,
    };

    payLoad.Version = options.Version;

    laralytics.sendPayload(payLoad, options);

  },

  sendPayload: function(_payLoad, _options) {

    var request = new XMLHttpRequest();
    request.open('POST', ''+ _options.API +'', true);
    request.setRequestHeader('Content-Type', 
      'application/x-www-form-urlencoded; charset=UTF-8');
    request.setRequestHeader('X-XSRF-TOKEN', 
      decodeURIComponent(laralytics.getCookie('XSRF-TOKEN')));
    request.send(JSON.stringify(_payLoad));
  },

  getCookie: function(cname) {
    var name = cname + '=';
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0)===' ') c = c.substring(1);
      if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
    }
    return '';
  },
};

laralytics.init();