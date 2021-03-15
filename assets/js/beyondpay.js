function bindBeyondPay(publicKey, formEventType, isTestMode){
  jQuery(function() {
    attachBeyondPay(publicKey, formEventType, isTestMode);
    jQuery( document.body ).on( 'updated_checkout' ,(e)=> {
      attachBeyondPay(publicKey, formEventType, isTestMode);
    });
  })
}

/**
 * Initializes and attaches the TokenPay iframe.
 * @param {string} publicKey
 * @returns {undefined}
 */
function attachBeyondPay(publicKey, formEventType, isTestMode){
  var firstRun = false;
  if (typeof (tokenpay) === 'undefined') {
    firstRun = true;
    tokenpay = TokenPay(publicKey, isTestMode);
  }

  tokenpay.initialize({
    dataElement: '#card',
    errorElement: '#errorMessage',
    useStyles: false
  });
  
  if(!firstRun){
    return;
  }
  jQuery(function() {
    var checkout_form = document.getElementById('beyond_pay_token').form;
    var token_used = false;
    var is_processing = false;
    jQuery(checkout_form).on( formEventType, function() {
        if(is_processing){
          return false;
        }
        var saved_methods_radio = checkout_form['wc-beyondpay-payment-token'];
        if(
          checkout_form.payment_method.value !== 'beyondpay' 
          || (saved_methods_radio && saved_methods_radio.value !== 'new')){
          return true;
        }
        is_processing = true;
        var token_input = document.getElementById("beyond_pay_token");
        if(token_used){
          token_input.value = "";
        }
        if(token_input.value){
            token_used = true;
            is_processing = false;
            return true;
        } else {
          tokenpay.createToken(
            function(res) {
              token_used = false;
              is_processing = false;
              token_input.value = res.token;
              document.getElementById('place_order').click();
            },
            function() {
              token_used = false;
              is_processing = false;
              token_input.value = "";
            }
          );
          return false;
        }
    });
  });
}