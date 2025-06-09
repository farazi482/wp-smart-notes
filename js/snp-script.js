jQuery(document).ready(function($) {
    $('body').mouseup(function(e) {
        const selected = window.getSelection();
        const text = selected.toString();
        if (text.length > 0) {
            $('.snp-popup').remove(); // Remove existing
            const $popup = $('<div class="snp-popup"><textarea placeholder="Add a personal note"></textarea><div class="sm-controls"><span class="cancel-btn">cancel</span><button>Save</button></div></div>');
            $('body').append($popup);
            $popup.css({ top: e.pageY + 10, left: e.pageX + 10 });

            $popup.find('button').on('click', function() {
                const comment = $popup.find('textarea').val();
                if (comment.length > 0) {
                    $.post(snp_ajax.ajax_url, {
                        action: 'snp_save_note',
                        text: text,
                        comment: comment,
                        url: window.location.href
                    }, function(response) {
                        alert(response.data);
                        $popup.remove();
                    });
                }
            });
        }
    });
     $(document).on('click', '.cancel-btn', function() {
        $('.snp-popup').remove();
    });
});