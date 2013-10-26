;(function (window, $) {
    'use strict';

    var
        url_modified = false,
        $post_title, $post_url;

    function save_code() {
        $('#form_element')
            .phpr()
            .post('on_save', {
                beforeSend: function () { window.phprTriggerSave(); },
                data: { redirect : 0 },
                customIndicator: window.LightLoadingIndicator,
                error: window.popupAjaxError,
                update: 'multi'
            })
            .send();

        return false;
    }

    function update_url_title() {
        var
            field_element_value = $(this).val();

        if (!url_modified) {
            $post_url.val(window.convert_text_to_url(field_element_value));
        }
    }

    $(function () {
        var
            is_new_record = ($('#new_record_flag').length) ? true : false;

        $post_title = $('#Blog_Post_title');
        $post_url = $('#Blog_Post_url_title');

        if (is_new_record) {
            $post_title.on('keyup change paste', update_url_title);
            $post_url.on('change', function () { url_modified = true; });
        }

        $('html').bindkey('meta+s, ctrl+s', save_code);
    });

}(this, this.jQuery));
