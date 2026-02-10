"use strict";

var amount = 0;
var invoices_id = '';
var card_progress = '';

$(document).on('click','.payment-button',function(){

  let save_button = $(this);
  save_button.addClass('btn-progress');

  amount = $(this).data("amount");
  invoices_id = $(this).data("id");
  
  if($('#payment-div').hasClass('d-none')){
    $('#payment-div').removeClass('d-none');
  }

  // Paypal
  if(paypal_client_id != ""){

    $('#paypal-button').empty();

    paypal.Buttons({

        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: amount
                    }
                }]
            });
        },

        onApprove: function(data, actions) {
          return actions.order.capture().then(function(details) {
              var status = 0;
              if(details.status == "COMPLETED"){
                status = 1; 
              }
              $.ajax({
                  type: "POST",
                  url: base_url+'invoices/order-completed/', 
                  data: "payment_type=Paypal&invoices_id="+invoices_id,
                  dataType: "json",
                  success: function(result) 
                  {	
                    if(result['error'] == false){
                        location.reload();
                    }else{
                      iziToast.error({
                        title: result['message'],
                        message: "",
                        position: 'topRight'
                      });
                    }
                  }        
            });
          });
        }
    }).render('#paypal-button').then(function() { 
      
    });
  }

  $('html, body').animate({
    scrollTop: $("#paypal-button").offset().top
  }, 1000);
  save_button.removeClass('btn-progress');
  
});

// Stripe
if(get_stripe_publishable_key != ""){
  var stripe = Stripe(get_stripe_publishable_key);
  var stripeButton = document.getElementById('stripe-button');
  stripeButton.addEventListener('click', function() {
    $('#stripe-button').addClass('disabled');

    fetch(base_url+'invoices/create-session/'+invoices_id+'/'+amount, {
      method: 'POST',
    })
    .then(function(response) {
      return response.json();
    })
    .then(function(session) {
      if(session.error != true){
        return stripe.redirectToCheckout({ sessionId: session.id });
      }
    })
    .then(function(result) {
      $('#stripe-button').removeClass('disabled');
      iziToast.error({
        title: something_wrong_try_again,
        message: "",
        position: 'topRight'
      });
    })
    .catch(function(error) {
      $('#stripe-button').removeClass('disabled');
      iziToast.error({
        title: something_wrong_try_again,
        message: "",
        position: 'topRight'
      });
    });
  });
}

// Razorpay
$(document).on('click','#razorpay-button',function(e){
  $('#razorpay-button').addClass('disabled');
  if(razorpay_key_id != ""){
    var options = {
      "key": razorpay_key_id,
      "amount": amount*100,
      "currency": currency_code,
      "name": "TimWork",
      "description": "Invoice",
      "handler": function (response){
        if(response.razorpay_payment_id){
          $.ajax({
            type: "POST",
            url: base_url+'invoices/order-completed/', 
            data: "payment_type=Razorpay&invoices_id="+invoices_id,
            dataType: "json",
            success: function(result) 
            {	
              if(result['error'] == false){
                  location.reload();
              }else{
                iziToast.error({
                  title: result['message'],
                  message: "",
                  position: 'topRight'
                });
              }
              $('#razorpay-button').removeClass('disabled');
            }        
          });
        }else{
          $('#razorpay-button').removeClass('disabled');
          iziToast.error({
            title: something_wrong_try_again,
            message: "",
            position: 'topRight'
          });
        }
      }
    };
    var rzp1 = new Razorpay(options);
    rzp1.on('payment.failed', function (response){
      $('#razorpay-button').removeClass('disabled');
      iziToast.error({
        title: something_wrong_try_again,
        message: "",
        position: 'topRight'
      });
    });
    rzp1.open();
    e.preventDefault();
  }else{
    $('#razorpay-button').removeClass('disabled');
    iziToast.error({
      title: something_wrong_try_again,
      message: "",
      position: 'topRight'
    });
  }
});

// Offline / Bank Transfer
$(document).on('click','#offline-button',function(){
  $('#offline-button').addClass('btn-progress');
  if(offline_bank_transfer != ""){
  swal({
		title: wait,
		text: we_will_contact_you_for_further_process_of_payment_as_soon_as_possible_click_ok_to_confirm,
		icon: 'warning',
		buttons: true,
		dangerMode: true,
		}).then((willDelete) => {
      if (willDelete) {
        $.ajax({
          type: "POST",
          url: base_url+'invoices/order-completed/', 
          data: "payment_type=Bank&invoices_id="+invoices_id,
          dataType: "json",
          success: function(result) 
          {	
            if(result['error'] == false){
                location.reload();
            }else{
              iziToast.error({
                title: result['message'],
                message: "",
                position: 'topRight'
              });
            }
            $('#offline-button').removeClass('btn-progress');
          }        
        });
      }
      $('#offline-button').removeClass('btn-progress');
    });
  }else{
    $('#razorpay-button').removeClass('btn-progress');
    iziToast.error({
      title: something_wrong_try_again,
      message: "",
      position: 'topRight'
    });
  }
});
