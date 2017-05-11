<?php if ( $message!="") : ?>
<div id="message" class="updated fade"><p><strong><?php echo $message; ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
<h2>Title</h2>
<table class="wp-list-table widefat fixed" cellspacing="0">
	<thead>
        <tr>
            <th scope="col" class="manage-column" style="">Sub Title</th>
        </tr>
	</thead>
	<tbody id="the-list">
        <tr>
            <td>
            	<form method="post" name="frm_tp" id="frm_tp" class="frm_tp" action="?page=tp_settings" enctype="multipart/form-data">
                <table width="100%">
                    <tr>
                    	<td width="180">Text Field</td>
                        <td>
                            <input type="text" name="textfield" id="textfield" value="<?php echo $options['textfield'];?>" />
                        </td>
                    </tr>
                    <tr>
                    	<td>Upload Field</td>
                        <td>
                            <input type="text" name="upload_field" id="upload_field" value="<?php echo $options['upload_field'];?>" class="textfield" />
                            <input class="upload_media_button button" type="button" name="btnupload" value="Upload" />
                        </td>
                    </tr>
                    
                    <tr>
                        <td></td>
                        <td><input type="submit" name="btnsave" id="btnsave" value="Update" class="button button-primary">
                        </td>
                    </tr>
                </table>
                </form>
            </td>
        </tr>
     </tbody>
</table>
</div>