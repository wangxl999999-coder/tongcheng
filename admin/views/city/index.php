<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">城市列表</h3>
        <button class="btn btn-primary" onclick="showModal('addModal')">+ 添加城市</button>
    </div>
    
    <form method="get" class="form-inline">
        <input type="hidden" name="c" value="city">
        <input type="hidden" name="a" value="index">
        <div class="form-group">
            <label>关键词：</label>
            <input type="text" name="keyword" value="<?php echo $keyword; ?>" placeholder="城市名称">
        </div>
        <div class="form-group">
            <label>状态：</label>
            <select name="status">
                <option value="">全部</option>
                <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>启用</option>
                <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>禁用</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">搜索</button>
        <a href="/admin/index.php?c=city&a=index" class="btn btn-default">重置</a>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>城市名称</th>
                <th>拼音</th>
                <th>首字母</th>
                <th>热门</th>
                <th>排序</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo $item['name']; ?></td>
                <td><?php echo $item['pinyin']; ?></td>
                <td><?php echo strtoupper($item['letter']); ?></td>
                <td><?php echo $item['is_hot'] ? '<span class="badge badge-danger">热门</span>' : ''; ?></td>
                <td><?php echo $item['sort']; ?></td>
                <td>
                    <span class="badge badge-<?php echo $item['status'] ? 'success' : 'default'; ?>">
                        <?php echo $item['status'] ? '启用' : '禁用'; ?>
                    </span>
                </td>
                <td><?php echo date('Y-m-d H:i', $item['created_at']); ?></td>
                <td>
                    <a href="/admin/index.php?c=city&a=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                    <a href="javascript:;" onclick="confirmDelete('/admin/index.php?c=city&a=delete&id=<?php echo $item['id']; ?>')" class="btn btn-sm btn-danger">删除</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($list)): ?>
            <tr>
                <td colspan="9" style="text-align: center; color: #999;">暂无数据</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total > $pageSize): ?>
    <div class="pagination">
        <?php 
        $totalPage = ceil($total / $pageSize);
        for ($i = 1; $i <= $totalPage; $i++):
            $url = "/admin/index.php?c=city&a=index&page=$i&keyword=$keyword&status=$status";
            if ($i == $page):
        ?>
        <span class="current"><?php echo $i; ?></span>
        <?php else: ?>
        <a href="<?php echo $url; ?>"><?php echo $i; ?></a>
        <?php endif; endfor; ?>
    </div>
    <?php endif; ?>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <iframe src="/admin/index.php?c=city&a=add" style="width:100%;height:500px;border:none;"></iframe>
    </div>
</div>
