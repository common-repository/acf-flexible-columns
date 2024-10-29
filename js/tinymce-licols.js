// JavaScript Document
//New jQuery JS Wrapper

jQuery(function($) { 

    //Update Content On Load
   /* $('.acf-field').each(function(){
      if( $(this).data('name') === 'list_layout') {
        var columns = $(this).find('select').val();
        //Find previous Editor value
        var editorcontent = $(this).prev('.acf-field').find('.wp-editor-area').val();
        //Add column class to all UL tags without a class
        editorcontent = editorcontent.replace(/<([uo])l>/g, '<$1l class="li-col-'+columns+'">');
        //Change column class on all UL tags already containing column class
        editorcontent = editorcontent.replace(/<([uo])l class=\"li\-col\-[1-6]\">/g, '<$1l class="li-col-'+columns+'">');
        //$(this).parent().parent().prev('.acf-field').find('.wp-editor-area').val(editorcontent);
        //Get Editor ID
        var tID = $(this).prev('.acf-field').find('textarea').attr('id');
        //Update Editor content
        $(tinymce.get(tID).getBody()).html(editorcontent);
      }
    });*/
    
    //Update Content on List Layout Change
    $('.acf-field[data-name="list_layout"]').on('change', 'select', function(){
        var columns = $(this).val();
        //Find previous Editor value
        var editorcontent = $(this).parent().parent().prev('.acf-field').find('.wp-editor-area').val();
        //Add column class to all UL tags without a class
        editorcontent = editorcontent.replace(/<([uo])l>/g, '<$1l class="li-col-'+columns+'">');
        //Change column class on all UL tags already containing column class
        editorcontent = editorcontent.replace(/<([uo])l class=\"li\-col\-[1-6]\">/g, '<$1l class="li-col-'+columns+'">');
        //$(this).parent().parent().prev('.acf-field').find('.wp-editor-area').val(editorcontent);
        //Get Editor ID
        var tID = $(this).parent().parent().prev('.acf-field').find('textarea').attr('id');
        //Update Editor content
        $(tinymce.get(tID).getBody()).html(editorcontent);
        
    });
   
});