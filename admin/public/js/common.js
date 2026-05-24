function confirmDelete(url, msg = '确定要删除吗？') {
    if (confirm(msg)) {
        $.post(url, {}, function(res) {
            if (res.code == 0) {
                alert(res.msg);
                location.reload();
            } else {
                alert(res.msg);
            }
        }, 'json');
    }
}

function showModal(id) {
    $('#' + id).addClass('show');
}

function hideModal(id) {
    $('#' + id).removeClass('show');
}

function uploadImage(fileInput, previewId, hiddenId) {
    var file = fileInput.files[0];
    if (!file) return;
    
    var formData = new FormData();
    formData.append('file', file);
    
    $.ajax({
        url: '/admin/index.php?c=upload&a=image',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.code == 0) {
                $('#' + previewId).attr('src', res.data.url).show();
                $('#' + hiddenId).val(res.data.url);
            } else {
                alert(res.msg);
            }
        },
        error: function() {
            alert('上传失败');
        }
    });
}

function formatDate(timestamp) {
    if (!timestamp) return '-';
    var date = new Date(timestamp * 1000);
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    var h = String(date.getHours()).padStart(2, '0');
    var i = String(date.getMinutes()).padStart(2, '0');
    var s = String(date.getSeconds()).padStart(2, '0');
    return y + '-' + m + '-' + d + ' ' + h + ':' + i + ':' + s;
}

function formatMoney(amount) {
    return '¥' + parseFloat(amount).toFixed(2);
}
