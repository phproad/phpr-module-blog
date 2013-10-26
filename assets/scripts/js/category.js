;(function (window, $) {
    'use strict';

    var
        url_modified = false,
        $cat_title, $cat_url;

    function update_url_title() {
        var
            field_element_value = $(this).val();

        if (!url_modified) {
            $cat_url.val(window.convert_text_to_url(field_element_value));
        }
    }

    $(function () {
        var
            is_new_record = ($('#new_record_flag').length) ? true : false;

        $cat_title = $('#Blog_Category_name');
        $cat_url = $('#Blog_Category_url_name');

        if (is_new_record) {
            $cat_title.on('keyup change paste', update_url_title);
            $cat_url.on('change', function () { url_modified = true; });
        }

    });

}(this, this.jQuery));
