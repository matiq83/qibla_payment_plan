<script language="javascript">
    jQuery(document).ready(function($) {
        attach_click_to_media_button('file','upload_media_button');
    });

    function attach_click_to_media_button(type,obj_class){
        jQuery('.'+obj_class).unbind( 'click' );
        jQuery('.'+obj_class).click(function() {
            upload_media_button =true;
            formfieldID=jQuery(this).prev().attr("id");
            formfield = jQuery("#"+formfieldID).attr('name');
            tb_show('', 'media-upload.php?type='+type+'&amp;TB_iframe=true');
            if(upload_media_button==true){
                var oldFunc = window.send_to_editor;
                window.send_to_editor = function(html) {
                    if(type=='image'){
                        fileURL = jQuery('img', html).attr('src');
                    }else{
                        fileURL = jQuery(html).attr('href');
                    }
                    jQuery("#"+formfieldID).val(fileURL);
                    tb_remove();
                    window.send_to_editor = oldFunc;
                }
            }
            upload_media_button=false;
        });
    }
</script>