jQuery(document).ready(function($){
    ajaxManager.run();
    
    /*
    ajaxManager.addReq({
                type: 'POST',
                url: ajax_url,
                data: data_array,
                success: function(response){
                    
                }
    });
     */
    if( $(".qpp_make_payment").length ) {        
        setTimeout(function(){
            console.log("set strip payment");
            $(".qpp_make_payment").attr( 'qpp_strip_org_price', $('#stripe-payment-data').attr('data-amount') );
            //$(".qpp_make_payment").trigger('change');
        },5000);       
    }
    
    if( $(".single_variation_wrap").length ) {
        setTimeout(function(){
            $("form.variations_form").unbind("click");
            $("form.variations_form .variations select").change(function(){
                //console.log("1");
                if( $(this).val() != "" && $(this).val() != "Choose an option" && $(this).val()!="choose-an-option" ) {
                    var obj_id = $(this).attr("id");
                    $("form.variations_form .variations select").each(function(){
                        //$("form.variations_form .variations select option[value='Choose an option']").remove();
                        var current_obj_id = $(this).attr('id');
                        //console.log(current_obj_id);
                        //console.log(obj_id);
                        if( current_obj_id != obj_id ) {
                            setTimeout(function(){
                                $('#'+current_obj_id).val('Choose an option');
                                $("button.single_add_to_cart_button").removeClass('disabled');
                                $(".single_variation_wrap").removeAttr('style');
                                $("a.reset_variations").remove();
                            },0);                  
                        }else{
                            if( $("form.variations_form .variations select").length === 1 ) {
                                $("button.single_add_to_cart_button").removeClass('disabled');
                                $(".single_variation_wrap").removeAttr('style');
                                $("a.reset_variations").remove();
                            }
                        }
                    });
                }else{
                    var disabled_obj = 0;
                    $("form.variations_form .variations select").each(function(){
                        if($(this).val()=="Choose an option" || $(this).val() == "" || $(this).val()=="choose-an-option" ){
                            disabled_obj++;
                        }
                    });
                    //console.log(disabled_obj);
                    if( disabled_obj === $("form.variations_form .variations select").length ) {
                        $("button.single_add_to_cart_button").addClass('disabled');
                        $(".single_variation_wrap").attr('style','display:none !important;');
                    }
                }
            });
            
            $("form.variations_form .variations select").click(function(){
                if($("form.variations_form .variations select option[value='']").length) {
                    $("form.variations_form .variations select option[value='']").remove();
                }
            });
        
        },500);        
                
    }
    if($("dl.variation").length) {
        var obj1 = $( "p:contains('Choose an option')" ).parent();
        var obj2 = obj1.prev();
        obj1.remove();
        obj2.remove();
    }
});

jQuery(document).on("DOMNodeRemoved",".stripe_checkout_app", qpp_strip_close);

function qpp_strip_close(){
    var $ = jQuery;
    if( !$(".woocommerce .blockOverlay").length ) {
        window.location.href=window.location.href;
    }
}
function qpp_set_price(obj) {
    var $ = jQuery;
    if( $(obj).is(":checked") ) {
        console.log("checked");
        var amount = $('#stripe-payment-data').attr('data-amount');//$(obj).attr('qpp_strip_org_price');
        console.log(amount);
        if( $(obj).val() == 50 ) {
            amount = amount/2;
            console.log(amount);
        }
        $('#stripe-payment-data').attr('data-amount',amount);
    }else{
        console.log("not checked");
        $('#stripe-payment-data').removeAttr('data-amount');
    }
}

var ajaxManager = (function() {
     var requests = [];

     return {
        addReq:  function(opt) {
            requests.push(opt);
        },
        removeReq:  function(opt) {
            if( jQuery.inArray(opt, requests) > -1 )
                requests.splice(jQuery.inArray(opt, requests), 1);
        },
        run: function() {
            var self = this,
                oriSuc;

            if( requests.length ) {
                oriSuc = requests[0].complete;

                requests[0].complete = function() {
                     if( typeof(oriSuc) === 'function' ) oriSuc();
                     requests.shift();
                     self.run.apply(self, []);
                };   

                jQuery.ajax(requests[0]);
            } else {
              self.tid = setTimeout(function() {
                 self.run.apply(self, []);
              }, 1000);
            }
        },
        stop:  function() {
            requests = [];
            clearTimeout(this.tid);
        }
     };
}());