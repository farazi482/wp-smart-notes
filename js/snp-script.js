jQuery(document).ready(function($) {
<<<<<<< Updated upstream
    $('body').mouseup(function(e) {
=======
    // Get customized text settings
    const texts = snp_ajax.texts || {};
    
    $('body').on('mouseup', function(e) {
>>>>>>> Stashed changes
        const selected = window.getSelection();
        const text = selected.toString();
        if (text.length > 0) {
<<<<<<< Updated upstream
            $('.snp-popup').remove(); // Remove existing
            const $popup = $('<div class="snp-popup"><textarea placeholder="Add a personal note"></textarea><button>Save</button></div>');
=======
            $('.snp-popup').remove(); // Remove existing popup

            const $popup = $(`
                <div class="snp-popup">
                    <textarea placeholder="${texts.placeholder_text || 'Add a personal note'}"></textarea>
                    <div class="sm-controls">
                        <span class="cancel-btn">${texts.cancel_button_text || 'Cancel'}</span>
                        <button class="save-btn">${texts.save_button_text || 'Save'}</button>
                    </div>
                </div>
            `);

>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
});
=======

    // Delete note handler
    $(document).on('click', '.snp-delete-note', function () {
        const confirmMessage = texts.delete_confirmation || 'Are you sure you want to remove this note?';
        if (!confirm(confirmMessage)) return;

        const noteEl = $(this).closest('.note-item');
        const noteID = $(this).data('id') || noteEl.data('id');

        $.post(snp_ajax.ajax_url, {
            action: 'snp_delete_note',
            note_id: noteID
        }, function (res) {
            if (res.success) {
                noteEl.fadeOut(300, function () { $(this).remove(); });
            } else {
                const errorPrefix = texts.error_prefix || 'Error: ';
                alert(errorPrefix + res.data);
            }
        });
    });
});
>>>>>>> Stashed changes
