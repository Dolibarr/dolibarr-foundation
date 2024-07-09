$(document).ready(function() {
    var tableBody = $('#table-body-add');
    var rowCount = 0;

    $('#addImageBtn').click(function() {
        rowCount++;

        var newRow = `
            <tr>
                <td><span class="icon-move"></span></td>
                <td class="rowCount" style="display:none;">${rowCount}</td>
                <td>
                    <input type="file" name="image_${rowCount}" class="image-input" accept="image/*" required >
                </td>
                <td>
                    <img src="#" style="display: none;" class="image-preview" width="100px">
                </td>
                <td class="td-deleteBtn">
                    <button type="button" class="btn deleteBtn"><i class="icon-delete"></i></button>
                </td>
            </tr>
        `;
        tableBody.append(newRow);

        // Attach file input change event
        var fileInput = tableBody.find('tr:last-child .image-input');
        fileInput.change(function() {
            var rowIndex = $(this).closest('tr').index();
            var imagePreview = tableBody.find('tr').eq(rowIndex).find('.image-preview');

            if (this.files && this.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    imagePreview.attr('src', e.target.result).css('display', 'block');
                };

                reader.readAsDataURL(this.files[0]);
            } else {
                imagePreview.attr('src', '').css('display', 'none');
            }
        });
    });

    // Delete row functionality
    tableBody.on('click', '.deleteBtn', function() {
        $(this).closest('tr').remove();
        updateRowNumbers();
    });

    // Function to update row numbers and input names
    function updateRowNumbers() {
        tableBody.find('tr').each(function(index) {
            var rowNum = index + 1;
            $(this).find('.rowCount').text(rowNum);
            $(this).find('.image-input').attr('name', `image_${rowNum}`);
        });
    }

    // Make table rows sortable add
    $('#table-body-add').sortable({
        handle: 'td:first-child', // Restrict drag start to the first cell (Position)
        update: function(event, ui) {
            updateRowNumbers(); // Update row numbers and input names after reordering
        },
        start: function(event, ui) {
          ui.item.addClass('dragging');
        },
        stop: function(event, ui) {
          ui.item.removeClass('dragging');
        },
    });
    
    document.getElementById('image_cover').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('image_preview');
                img.src = e.target.result;
                img.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
    
    document.getElementById('virtual_product_file').addEventListener('change', function(event) {
        var fileInfoDiv = document.getElementById('field-info-virtual_product_file');
        var fileName = event.target.files[0] ? event.target.files[0].name : "Aucun fichier sélectionné";
        fileInfoDiv.textContent = fileName;
    });
    document.getElementById('image_cover').addEventListener('change', function(event) {
        var fileInfoDiv = document.getElementById('field-info-image-cover');
        var fileName = event.target.files[0] ? event.target.files[0].name : "Aucun fichier sélectionné";
        fileInfoDiv.textContent = fileName;
    });

    // Make table rows sortable edit
    $('#table-body-edit').sortable({
        handle: 'td:first-child',
        update: function(event, ui) {
            updateEditRowNumbers();
    
            var order = getOrder();
            $.ajax({
                url: edit_product_url,
                type: 'POST',
                dataType: 'json',
                data: { action: 'update_order', order: order },
                success: function(response) {
                    showNotification(response.message);
                    if (response.success) {
                        // Optionnel : mettre à jour l'interface utilisateur
                    }
                },
                fail: function(response) {
                    alert(response.message);
                }
            });
        },
        start: function(event, ui) {
            ui.item.addClass('dragging');
        },
        stop: function(event, ui) {
            ui.item.removeClass('dragging');
        },
    });

    function getOrder() {
        var order = {};
        $('#table-body-edit tr').each(function(index, element) {
            var id = $(element).find('.deleteBtn').data('id');
            var position = index + 1;
            order[id] = position;
        });
        return order;
    }


    $('#addImageBtn2').on('click', function() {
        $('#fileInput2').click();
    });

    $('#fileInput2').on('change', function(e) {
        e.preventDefault();
        var formData = new FormData();
        var position_for_new_photo = $('#table-body-edit tr').length + 1;
        var product_id = $('#product_to_edit').text();

        formData.append('product_id', product_id);
        formData.append('action', 'add_product_image');
        formData.append('position', position_for_new_photo);
        formData.append('file', this.files[0]);
        
        $.ajax({
            url: edit_product_url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false
        }).done(function(response) {
            showNotification(response.message);
            if (response.success) {
                location.reload(); // Recharge la page
            }
        }).fail(function(response) {
            alert(response.message);
        });
    });

    
    $('.deleteBtn').on('click', function(e) {
        e.preventDefault();
        var id_image = $(this).data('id');
        $.ajax({
            url: edit_product_url,
            type: "POST",
            dataType: "json", // On attend une réponse JSON du serveur
            data: { action: 'delete_image', 'id_image': id_image }
        }).done(function(response) {
            showNotification(response.message);
            if (response.success) {
                $('tr[data-id="' + id_image + '"]').remove();
                //updateEditRowNumbers();
            }
        }).fail(function() {
            alert('error');
        });
    });


    function updateEditRowNumbers() {
        $('#table-body-edit tr').each(function(index) {
            $(this).find('.rowCount').text(index + 1);
        });
    }

    function showNotification(message) {
        $('.notification').text(message).fadeIn().delay(2000).fadeOut();
    }


    // Get references to the checkboxes and the submit button
    const dolibarrTermsCheckbox = document.getElementById('dolibarr_terms');
    const moduleWikiCheckbox = document.getElementById('module_wiki');
    const submitButton = document.getElementById('sub');

    // Function to check the state of the checkboxes and update the button state
    function updateButtonState() {
        if (dolibarrTermsCheckbox.checked && moduleWikiCheckbox.checked) {
            submitButton.disabled = false;
            submitButton.style.opacity = 1.0;
            submitButton.classList.remove('button_large_disabled');
            submitButton.classList.add('button_large');
        } else {
            submitButton.disabled = true;
            submitButton.style.opacity = 0.5;
            submitButton.classList.add('button_large_disabled');
            submitButton.classList.remove('button_large');
        }
    }

    // Add event listeners to the checkboxes to monitor changes
    dolibarrTermsCheckbox.addEventListener('change', updateButtonState);
    moduleWikiCheckbox.addEventListener('change', updateButtonState);

    // Initial check to set the button state based on the initial checkbox states
    updateButtonState();


});


