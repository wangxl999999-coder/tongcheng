<style>
.dashboard {
    background: #0f1428;
    min-height: calc(100vh - 108px);
    padding: 20px;
    color: #fff;
    margin: -24px;
}
.dashboard-header {
    text-align: center;
    margin-bottom: 20px;
}
.dashboard-header h1 {
    font-size: 28px;
    background: linear-gradient(90deg, #00d2ff, #3a7bd5);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 8px;
}
.dashboard-header .time {
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
}
.data-overview {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
.data-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(0, 210, 255, 0.2);
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.data-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #00d2ff, #3a7bd5);
}
.data-card .label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 13px;
    margin-bottom: 8px;
}
.data-card .value {
    font-size: 28px;
    font-weight: bold;
    color: #00d2ff;
}
.data-card .sub {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
    margin-top: 4px;
}
.data-card .trend-up { color: #67c23a; }
.data-card .trend-down { color: #f56c6c; }
.chart-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}
.chart-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
}
.chart-box {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(0, 210, 255, 0.2);
    border-radius: 8px;
    padding: 16px;
    height: 320px;
}
.chart-title {
    font-size: 16px;
    color: #00d2ff;
    margin-bottom: 12px;
    padding-left: 10px;
    border-left: 3px solid #00d2ff;
}
.chart-container {
    height: calc(100% - 40px);
}
.rank-list {
    height: calc(100% - 40px);
    overflow-y: auto;
}
.rank-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.rank-item:last-child { border-bottom: none; }
.rank-num {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    margin-right: 10px;
}
.rank-item:nth-child(1) .rank-num { background: #ff4757; }
.rank-item:nth-child(2) .rank-num { background: #ff7f50; }
.rank-item:nth-child(3) .rank-num { background: #ffa502; }
.rank-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-right: 10px;
    object-fit: cover;
}
.rank-info { flex: 1; }
.rank-name { font-size: 13px; margin-bottom: 2px; }
.rank-sub { font-size: 11px; color: rgba(255, 255, 255, 0.5); }
.rank-value { color: #00d2ff; font-weight: bold; }
</style>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>同城预约上门服务系统 - 数据大屏</h1>
        <div class="time" id="currentTime"></div>
    </div>
    
    <div class="data-overview">
        <div class="data-card">
            <div class="label">今日订单</div>
            <div class="value"><?php echo $todayOrder['count']; ?></div>
            <div class="sub">
                昨日 <?php echo $yesterdayOrder['count']; ?>
                <span class="<?php echo $todayOrder['count'] >= $yesterdayOrder['count'] ? 'trend-up' : 'trend-down'; ?>">
                    <?php echo $todayOrder['count'] >= $yesterdayOrder['count'] ? '↑' : '↓'; ?>
                </span>
            </div>
        </div>
        <div class="data-card">
            <div class="label">今日收入</div>
            <div class="value">¥<?php echo number_format($todayOrder['amount'], 0); ?></div>
            <div class="sub">
                昨日 ¥<?php echo number_format($yesterdayOrder['amount'], 0); ?>
                <span class="<?php echo $todayOrder['amount'] >= $yesterdayOrder['amount'] ? 'trend-up' : 'trend-down'; ?>">
                    <?php echo $todayOrder['amount'] >= $yesterdayOrder['amount'] ? '↑' : '↓'; ?>
                </span>
            </div>
        </div>
        <div class="data-card">
            <div class="label">本月订单</div>
            <div class="value"><?php echo $monthOrder['count']; ?></div>
            <div class="sub">累计收入 ¥<?php echo number_format($monthOrder['amount'], 0); ?></div>
        </div>
        <div class="data-card">
            <div class="label">总用户数</div>
            <div class="value"><?php echo $totalUser; ?></div>
            <div class="sub">注册用户</div>
        </div>
        <div class="data-card">
            <div class="label">服务人员</div>
            <div class="value"><?php echo $onlineTechnician; ?>/<?php echo $totalTechnician; ?></div>
            <div class="sub">在线/总数</div>
        </div>
        <div class="data-card">
            <div class="label">入驻商家</div>
            <div class="value"><?php echo $totalMerchant; ?></div>
            <div class="sub">待处理订单 <?php echo $pendingOrder; ?></div>
        </div>
    </div>
    
    <div class="chart-row">
        <div class="chart-box">
            <div class="chart-title">订单趋势（近15天）</div>
            <div id="orderTrend" class="chart-container"></div>
        </div>
        <div class="chart-box">
            <div class="chart-title">服务类型占比</div>
            <div id="serviceType" class="chart-container"></div>
        </div>
        <div class="chart-box">
            <div class="chart-title">24小时订单分布</div>
            <div id="timeDistribution" class="chart-container"></div>
        </div>
    </div>
    
    <div class="chart-row-2">
        <div class="chart-box">
            <div class="chart-title">城市订单排行</div>
            <div id="cityRank" class="chart-container"></div>
        </div>
        <div class="chart-box">
            <div class="chart-title">收入统计（近6个月）</div>
            <div id="incomeTrend" class="chart-container"></div>
        </div>
        <div class="chart-box">
            <div class="chart-title">服务人员排行</div>
            <div class="rank-list">
                <?php foreach ($technicianRank as $k => $t): ?>
                <div class="rank-item">
                    <div class="rank-num"><?php echo $k + 1; ?></div>
                    <img src="<?php echo $t['avatar'] ?: '/admin/public/images/avatar.png'; ?>" class="rank-avatar">
                    <div class="rank-info">
                        <div class="rank-name"><?php echo $t['name']; ?></div>
                        <div class="rank-sub"><?php echo $t['count']; ?>单 · ¥<?php echo number_format($t['income'], 0); ?></div>
                    </div>
                    <div class="rank-value"><?php echo $t['count']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
<script>
function updateTime() {
    var now = new Date();
    document.getElementById('currentTime').innerText = now.toLocaleString('zh-CN');
}
updateTime();
setInterval(updateTime, 1000);

var axisColor = {
    line: 'rgba(255, 255, 255, 0.1)',
    label: 'rgba(255, 255, 255, 0.6)',
    split: 'rgba(255, 255, 255, 0.05)'
};

var orderTrend = echarts.init(document.getElementById('orderTrend'));
orderTrend.setOption({
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: {
        type: 'category',
        data: <?php echo json_encode($orderTrend['days']); ?>,
        axisLine: { lineStyle: { color: axisColor.line } },
        axisLabel: { color: axisColor.label }
    },
    yAxis: {
        type: 'value',
        axisLine: { lineStyle: { color: axisColor.line } },
        axisLabel: { color: axisColor.label },
        splitLine: { lineStyle: { color: axisColor.split } }
    },
    series: [{
        type: 'line',
        smooth: true,
        data: <?php echo json_encode($orderTrend['counts']); ?>,
        areaStyle: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(0, 210, 255, 0.5)' },
                { offset: 1, color: 'rgba(0, 210, 255, 0)' }
            ])
        },
        lineStyle: { color: '#00d2ff', width: 2 },
        itemStyle: { color: '#00d2ff' }
    }]
});

var serviceType = echarts.init(document.getElementById('serviceType'));
serviceType.setOption({
    tooltip: { trigger: 'item' },
    legend: {
        orient: 'vertical',
        right: 10,
        top: 'center',
        textStyle: { color: 'rgba(255, 255, 255, 0.6)' }
    },
    series: [{
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['35%', '50%'],
        avoidLabelOverlap: false,
        itemStyle: { borderRadius: 4, borderColor: '#0f1428', borderWidth: 2 },
        label: { show: false },
        emphasis: { label: { show: true, color: '#fff' } },
        data: <?php echo json_encode($serviceTypeRatio); ?>,
        color: ['#00d2ff', '#3a7bd5', '#67c23a', '#e6a23c', '#f56c6c', '#909399', '#ff6b81', '#7bed9f']
    }]
});

var timeDistribution = echarts.init(document.getElementById('timeDistribution'));
timeDistribution.setOption({
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: {
        type: 'category',
        data: <?php echo json_encode($timeDistribution['hours']); ?>,
        axisLine: { lineStyle: { color: axisColor.line } },
        axisLabel: { color: axisColor.label, fontSize: 10, interval: 2 }
    },
    yAxis: {
        type: 'value',
        axisLine: { lineStyle: { color: axisColor.line } },
        axisLabel: { color: axisColor.label },
        splitLine: { lineStyle: { color: axisColor.split } }
    },
    series: [{
        type: 'bar',
        data: <?php echo json_encode($timeDistribution['counts']); ?>,
        itemStyle: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: '#00d2ff' },
                { offset: 1, color: '#3a7bd5' }
            ]),
            borderRadius: [4, 4, 0, 0]
        }
    }]
});

var cityRank = echarts.init(document.getElementById('cityRank'));
cityRank.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: {
        type: 'value',
        axisLine: { lineStyle: { color: axisColor.line } },
        axisLabel: { color: axisColor.label },
        splitLine: { lineStyle: { color: axisColor.split } }
    },
    yAxis: {
        type: 'category',
        data: <?php echo json_encode($cityOrderRank['names']); ?>,
        axisLine: { lineStyle: { color: axisColor.line } },
        axisLabel: { color: axisColor.label }
    },
    series: [{
        type: 'bar',
        data: <?php echo json_encode($cityOrderRank['counts']); ?>,
        itemStyle: {
            color: new echarts.graphic.LinearGradient(1, 0, 0, 0, [
                { offset: 0, color: '#67c23a' },
                { offset: 1, color: '#3a7bd5' }
            ]),
            borderRadius: [0, 4, 4, 0]
        }
    }]
});

var incomeTrend = echarts.init(document.getElementById('incomeTrend'));
incomeTrend.setOption({
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: {
        type: 'category',
        data: <?php echo json_encode($incomeTrend['months']); ?>,
        axisLine: { lineStyle: { color: axisColor.line } },
        axisLabel: { color: axisColor.label, fontSize: 11 }
    },
    yAxis: {
        type: 'value',
        axisLine: { lineStyle: { color: axisColor.line } },
        axisLabel: { color: axisColor.label },
        splitLine: { lineStyle: { color: axisColor.split } }
    },
    series: [{
        type: 'bar',
        data: <?php echo json_encode($incomeTrend['incomes']); ?>,
        itemStyle: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: '#ffa502' },
                { offset: 1, color: '#ff7f50' }
            ]),
            borderRadius: [4, 4, 0, 0]
        },
        label: {
            show: true,
            position: 'top',
            color: '#ffa502',
            fontSize: 10,
            formatter: function(params) { return '¥' + params.value; }
        }
    }]
});

window.addEventListener('resize', function() {
    orderTrend.resize();
    serviceType.resize();
    timeDistribution.resize();
    cityRank.resize();
    incomeTrend.resize();
});
</script>
