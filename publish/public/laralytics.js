'use strict';

var laralytics = function(){

  // Param of laralytics
  var defaultParam = {
    API : '/laralytics',
    Limit : 0,
  };

  //Object track with : screen size, mouse position, browser, all click event
  var payLoad = {
    info: [],
    click: [],
    custom: [],
  };

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
  var checkLength = function(_finalParam) {
    var totalArray = payLoad.click.length + payLoad.custom.length;

    if(_finalParam.Limit === 0){
      return ;
    }else if(totalArray === _finalParam.Limit ||
      totalArray >= _finalParam.Limit){
        sendPayload(_finalParam);
        payLoad = {
          click: [],
          custom: [],
        };
    }
  };

  // Custom click with data attributes
  var custom = function(_finalParam) {
    payLoad.custom.push({
      type: event.type,
      x: event.clientX,
      y: event.clientY,
      datetime : Math.floor(new Date().getTime() / 1000),
      element : event.target.id || event.target.className ||
        event.target.localName
    });
    checkLength(_finalParam);
  };

  // Send payload to API
  var sendPayload = function(_options) {

    var request = new XMLHttpRequest();
    request.open('POST', ''+ _options.API +'', true);
    request.setRequestHeader('Content-Type',
      'application/json; charset=UTF-8');
    request.setRequestHeader('X-XSRF-TOKEN',
      decodeURIComponent(getCookie('XSRF-TOKEN')));
    request.send(JSON.stringify(payLoad));
  };

  // Init laralytics to track click / custom click
  var init = function(customParam) {

    // Set param for analytics
    var finalParam = extend({}, defaultParam, customParam);

    // Set first tracks of user informations
    payLoad.info = {
      browser: window.navigator.userAgent,
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
      payLoad.click.push({
        x: event.clientX,
        y: event.clientY,
        datetime : Math.floor(new Date().getTime() / 1000),
        element : event.target.id || event.target.className ||
          event.target.localName
      });
      checkLength(finalParam);
    });

    window.onbeforeunload = function(){
      sendPayload(finalParam);
    };
  };

  return{
    init:init,
  };
}();
