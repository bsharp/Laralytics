'use strict';

var payLoad = {};

var laralytics = {

  init: function(customParam) {

    // Number click function
    var i = 0;
    var x = 0;

    // Object track with : screen size, mouse position, browser, all click event
    payLoad = {};

    // Options of laralytics when DOM is loaded
    var defaultParam = {
      API : '/laralytics',
      Version : null,
      Limit : 0,
    };

    // Set param for analytics
    var finalParam = laralytics.extend({}, defaultParam, customParam);

    // Set first tracks of user informations
    payLoad.Version = finalParam.Version;
    payLoad.Browser = window.navigator.userAgent;

    var elemCustom = document.querySelectorAll('[data-laralytics]');

    for (var j = 0; j < elemCustom.length; j++){
      elemCustom[j].addEventListener(''+
        elemCustom[j].getAttribute('data-laralytics')+'',function(event){
          var nameEvent = 'custom'+event.type + x;
          payLoad[nameEvent] = {
            type: event.type,
            x: event.clientX,
            y: event.clientY,
            date : new Date(),
          };
          x++;
          laralytics.checkLength(payLoad, finalParam);
      });
    }

    document.addEventListener('click', function(event){
      var nameEvent = event.type + i;
      payLoad[nameEvent] = {
        x: event.clientX,
        y: event.clientY,
        date : new Date(),
      };
      i++;
      laralytics.checkLength(payLoad, finalParam);
    });

    window.onbeforeunload = function(){
      laralytics.sendPayload(payLoad, finalParam);
    };
  },

  // Send payload to API
  sendPayload: function(_payLoad, _options) {

    var request = new XMLHttpRequest();
    request.open('POST', ''+ _options.API +'', true);
    request.setRequestHeader('Content-Type', 
      'application/x-www-form-urlencoded; charset=UTF-8');
    request.setRequestHeader('X-XSRF-TOKEN', 
      decodeURIComponent(laralytics.getCookie('XSRF-TOKEN')));
    request.send(JSON.stringify(_payLoad));
  },

  // Get value of a cookie
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

  // Merge param if custom set
  extend: function(out) {
    out = out || {};

    for (var i = 1; i < arguments.length; i++) {
      if (!arguments[i])
        continue;

      for (var key in arguments[i]) {
        if (arguments[i].hasOwnProperty(key))
          out[key] = arguments[i][key];
      }
    }

    return out;
  },

  checkLength: function(_payLoad, _finalParam) {
    if(Object.keys(_payLoad).length === _finalParam.Limit){
      laralytics.sendPayload(_payLoad, _finalParam);
      payLoad = {};
    }
  },
};

laralytics.init();