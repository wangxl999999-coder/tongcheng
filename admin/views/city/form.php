<div class="panel" style="box-shadow:none; margin:0;">
    <div class="panel-header">
        <h3 class="panel-title"><?php echo $title; ?></h3>
        <button class="close-btn" onclick="parent.hideModal('addModal')">×</button>
    </div>
    <form id="cityForm" class="form">
        <input type="hidden" name="id" value="<?php echo $info['id'] ?? ''; ?>">
        <table class="form-table">
            <tr>
                <td>城市名称：</td>
                <td><input type="text" name="name" value="<?php echo $info['name'] ?? ''; ?>" required></td>
            </tr>
            <tr>
                <td>拼音：</td>
                <td><input type="text" name="pinyin" value="<?php echo $info['pinyin'] ?? ''; ?>" placeholder="如：beijing"></td>
            </tr>
            <tr>
                <td>首字母：</td>
                <td><input type="text" name="letter" value="<?php echo $info['letter'] ?? ''; ?>" maxlength="1" placeholder="如：b"></td>
            </tr>
            <tr>
                <td>排序：</td>
                <td><input type="number" name="sort" value="<?php echo $info['sort'] ?? 0; ?>" placeholder="数字越小越靠前"></td>
            </tr>
            <tr>
                <td>热门城市：</td>
                <td>
                    <label><input type="radio" name="is_hot" value="1" <?php echo ($info['is_hot'] ?? 0) == 1 ? 'checked' : ''; ?>> 是</label>
                    <label><input type="radio" name="is_hot" value="0" <?php echo ($info['is_hot'] ?? 0) == 0 ? 'checked' : ''; ?>> 否</label>
                </td>
            </tr>
            <tr>
                <td>状态：</td>
                <td>
                    <label><input type="radio" name="status" value="1" <?php echo ($info['status'] ?? 1) == 1 ? 'checked' : ''; ?>> 启用</label>
                    <label><input type="radio" name="status" value="0" <?php echo ($info['status'] ?? 1) == 0 ? 'checked' : ''; ?>> 禁用</label>
                </td>
            </tr>
        </table>
        <div style="text-align:center; padding:20px;">
            <button type="submit" class="btn btn-primary">保存</button>
            <button type="button" class="btn btn-default" onclick="parent.hideModal('addModal')">取消</button>
        </div>
    </form>
</div>

<script>
$('#cityForm').submit(function(e) {
    e.preventDefault();
    var url = '<?php echo isset($info) ? "/admin/index.php?c=city&a=edit&id=" . $info["id"] : "/admin/index.php?c=city&a=add"; ?>';
    $.post(url, $(this).serialize(), function(res) {
        if (res.code == 0) {
            alert(res.msg);
            parent.location.reload();
        } else {
            alert(res.msg);
        }
    }, 'json');
});
</script>
