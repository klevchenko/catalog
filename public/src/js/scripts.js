$( document ).ready(function() {
    if($('form[name="create_new_catalog"]').length){

        $('form[name="create_new_catalog"]').submit(function (){
            $(this).addClass('submited');
        });

    }
});