'use strict';

var laralytics = function(){

  // Param of laralytics
  var defaultParam = {
    API : '/laralytics',
    Version : null,
    Limit : 0,
  };

  //Object track with : screen size, mouse position, browser, all click event
  var payLoad = {};

  // Number click function
  var i = 0;
  var x = 0;

  // Get value of a cookie
  var getCookie = function(cname) {
    var name = cname + '=';
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0)===' ') c = c.substring(1);
      if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
    }
    return '';
  };

  // Merge param if custom set
  var extend = function(out) {
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
  };

  // Send request when a limit is set
  var checkLength = function(_payLoad, _finalParam) {
    if(Object.keys(_payLoad).length === _finalParam.Limit){
      sendPayload(_payLoad, _finalParam);
      payLoad = {};
    }
  };

  // Custom click with data attributes
  var custom = function(_finalParam) {
    var nameEvent = 'custom'+event.type + x;
    payLoad[nameEvent] = {
      type: event.type,
      x: event.clientX,
      y: event.clientY,
      date : Math.floor(new Date().getTime() / 1000),
      elem : event.target.id || event.target.className ||
          event.target.localName
    };
    x++;
    checkLength(payLoad, _finalParam);
  };

  // Send payload to API
  var sendPayload = function(_payLoad, _options) {

    var request = new XMLHttpRequest();
    request.open('POST', ''+ _options.API +'', true);
    request.setRequestHeader('Content-Type',
      'application/json; charset=UTF-8');
    request.setRequestHeader('X-XSRF-TOKEN',
      decodeURIComponent(getCookie('XSRF-TOKEN')));
    request.send(JSON.stringify(_payLoad));
  };

  // Init laralytics to track click / custom click
  var init = function(customParam) {

    // Set param for analytics
    var finalParam = extend({}, defaultParam, customParam);

    // Set first tracks of user informations
    payLoad.Version = finalParam.Version;
    payLoad.Browser = window.navigator.userAgent;
    payLoad.Userscreen = {
      browserWidth : window.innerWidth,
      browserHeight : window.innerHeight,
      deviceWidth : window.screen.width,
      deviceHeight : window.screen.height,
    };

    var elemCustom = document.querySelectorAll('[data-laralytics]');

    for (var j = 0; j < elemCustom.length; j++){
      elemCustom[j].addEventListener(''+
        elemCustom[j].getAttribute('data-laralytics')+'',
        custom.bind(finalParam));
    }

    document.addEventListener('click', function(event){
      var nameEvent = event.type + i;
      payLoad[nameEvent] = {
        x: event.clientX,
        y: event.clientY,
        date : Math.floor(new Date().getTime() / 1000),
        elem : event.target.id || event.target.className ||
          event.target.localName
      };
      i++;
      checkLength(payLoad, finalParam);
    });

    window.onbeforeunload = function(){
      sendPayload(payLoad, finalParam);
    };
  };

  return{
    init:init,
  };
}();

laralytics.init();
