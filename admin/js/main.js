jQuery(document).ready(function ($) {
  let slideIndex = $('#slides-container .slide').length; // Get the current number of slides

  // Add a new slide on button click
  $('#add-slide').on('click', function () {
    slideIndex++;
    const newSlide = `
        <div class="slide" data-index="${slideIndex}">
            <h4>Slide ${slideIndex}</h4>
            <div class="editor-container">
            <button type="button" class="button insert-media add_media"><span class="wp-media-buttons-icon"></span>Add Media</button>
                <textarea id="editor-${slideIndex}" name="slides[${slideIndex}][content]" placeholder="Slide Content"></textarea>
                
            </div>
            <button type="button" class="button remove-slide">Remove</button>
        </div>
    `;
    $('#slides-container').append(newSlide);

    // Initialize the WordPress editor with full TinyMCE options (includes Add Media button)
    tinymce.execCommand('mceAddEditor', true, `editor-${slideIndex}`);

    // Add Media Button
    $(document).on('click', '.add-media', function () {
      const editor = tinymce.get($(this).closest('.slide').find('textarea').attr('id'));

      wp.media.editor.send.attachment = function (props, attachment) {
        const value = editor.getContent();
        editor.setContent(`${value}<img src="${attachment.sizes.full.url}" />`);

      };
      wp.media.editor.open($(this).closest('.slide').find('textarea').attr('id'));
    });

    // Focus on the new editor
    const newEditor = tinymce.get(`editor-${slideIndex}`);
    newEditor.focus();

  });

  // Remove slide on click
  $(document).on('click', '.remove-slide', function () {
    const editorID = $(this).closest('.slide').find('textarea').attr('id');
    const editor = tinymce.get(editorID);
    editor.remove(); // Remove the editor instance
    $(this).closest('.slide').remove(); // Remove the slide from DOM
  });
});

