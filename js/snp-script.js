jQuery(document).ready(function($) {
    $('body').on('mouseup', function(e) {
        const selected = window.getSelection();
        const text = selected.toString().trim();

        if (text.length > 0) {
            $('.snp-popup').remove(); // Remove existing popup

            const $popup = $(`
                <div class="snp-popup">
                    <textarea placeholder="Add a personal note"></textarea>
                    <div class="sm-controls">
                        <span class="cancel-btn">Cancel</span>
                        <button class="save-btn">Save</button>
                    </div>
                </div>
            `);

            $('body').append($popup);
            $popup.css({ top: e.pageY + 10, left: e.pageX + 10 });

            $popup.find('.save-btn').on('click', function() {
                const comment = $popup.find('textarea').val().trim();
                if (comment.length > 0) {
                    $.post(snp_ajax.ajax_url, {
                        action: 'snp_save_note',
                        text: text,
                        comment: comment,
                        url: window.location.href
                    }, function(response) {
                        alert(response.data);
                        $popup.remove();
                        location.reload(); // Optional: Refresh to show note
                    });
                }
            });

            $popup.find('.cancel-btn').on('click', function() {
                $popup.remove();
            });
        }
    });

    // Delete note handler
    $(document).on('click', '.snp-delete-note', function () {
        if (!confirm('Are you sure you want to remove this note?')) return;

        const noteEl = $(this).closest('.note-item');
        const noteID = $(this).data('id') || noteEl.data('id');

        $.post(snp_ajax.ajax_url, {
            action: 'snp_delete_note',
            note_id: noteID
        }, function (res) {
            if (res.success) {
                noteEl.fadeOut(300, function () { $(this).remove(); });
            } else {
                alert('Error: ' + res.data);
            }
        });
    });
});
