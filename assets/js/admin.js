jQuery( document ).ready( function ( e ) {

    function removeParent() {
        jQuery(this).parent().remove();
    }

    jQuery('body').on('click', '.repeater-remover', removeParent);

    jQuery('.repeater-adder').click(function() {
        $new_group = jQuery(this).parent().children('.repeater-fields.template').clone();
        $new_group
            .removeClass('template')
            .css('display', 'inline-block')
            .children('p')
            .children('input, select, textarea').each(function() {
                jQuery(this).attr('name', jQuery(this).attr('name').replace('-template', '[]'));
            });


        jQuery(this).before($new_group);
    })
});