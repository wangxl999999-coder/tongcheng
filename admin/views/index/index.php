<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-icon blue">📋</div>
        <div class="stat-info">
            <div class="label">今日订单数</div>
            <div class="value"><?php echo $todayOrderCount; ?></div>
            <div class="sub">累计：<?php echo $totalOrder; ?> 单</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
            <div class="label">今日金额</div>
            <div class="value">¥<?php echo number_format($todayAmount, 2); ?></div>
            <div class="sub">累计：¥<?php echo number_format($totalAmount, 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">👤</div>
        <div class="stat-info">
            <div class="label">今日新增用户</div>
            <div class="value"><?php echo $todayNewUser; ?></div>
            <div class="sub">累计：<?php echo $totalUser; ?> 人</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">⏳</div>
        <div class="stat-info">
            <div class="label">待处理订单</div>
            <div class="value"><?php echo $pendingOrder; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">📦</div>
        <div class="stat-info">
            <div class="label">待派单数量</div>
            <div class="value"><?php echo $pendingDispatch; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">🔵</div>
        <div class="stat-info">
            <div class="label">服务人员在线</div>
            <div class="value"><?php echo $onlineTechnician; ?></div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title">订单趋势（近7天）</h3>
        </div>
        <div id="orderTrendChart" style="height: 300px;"></div>
    </div>
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title">服务类型占比</h3>
        </div>
        <div id="serviceTypeChart" style="height: 300px;"></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">最近订单</h3>
        <a href="/admin/index.php?c=order&a=index" class="btn btn-sm btn-primary">查看全部</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>订单号</th>
                <th>用户</th>
                <th>服务项目</th>
                <th>金额</th>
                <th>状态</th>
                <th>下单时间</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $order): ?>
            <tr>
                <td><?php echo $order['order_no']; ?></td>
                <td><?php echo $order['nickname'] ?: '匿名用户'; ?></td>
                <td><?php echo $order['service_name']; ?></td>
                <td>¥<?php echo number_format($order['pay_amount'], 2); ?></td>
                <td><span class="badge badge-<?php echo $statusColor[$order['status']]; ?>"><?php echo $statusMap[$order['status']]; ?></span></td>
                <td><?php echo date('Y-m-d H:i', $order['created_at']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
            <tr>
                <td colspan="6" style="text-align: center; color: #999;">暂无数据</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
<script>
var orderTrend = echarts.init(document.getElementById('orderTrendChart'));
var option1 = {
    tooltip: { trigger: 'axis' },
    legend: { data: ['订单数', '收入金额'] },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'category', data: <?php echo json_encode($orderTrend['days']); ?> },
    yAxis: [
        { type: 'value', name: '订单数' },
        { type: 'value', name: '金额(元)' }
    ],
    series: [
        {
            name: '订单数',
            type: 'bar',
            data: <?php echo json_encode($orderTrend['orderCounts']); ?>,
            itemStyle: { color: '#409eff' }
        },
        {
            name: '收入金额',
            type: 'line',
            yAxisIndex: 1,
            data: <?php echo json_encode($orderTrend['amounts']); ?>,
            itemStyle: { color: '#67c23a' },
            smooth: true
        }
    ]
};
orderTrend.setOption(option1);

var serviceTypeChart = echarts.init(document.getElementById('serviceTypeChart'));
var option2 = {
    tooltip: { trigger: 'item' },
    legend: { orient: 'vertical', left: 'left' },
    series: [{
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        itemStyle: { borderRadius: 10, borderColor: '#fff', borderWidth: 2 },
        label: { show: false, position: 'center' },
        emphasis: { label: { show: true, fontSize: 20, fontWeight: 'bold' } },
        labelLine: { show: false },
        data: <?php echo json_encode($serviceTypeRatio); ?>
    }]
};
serviceTypeChart.setOption(option2);

window.addEventListener('resize', function() {
    orderTrend.resize();
    serviceTypeChart.resize();
});
</script>
