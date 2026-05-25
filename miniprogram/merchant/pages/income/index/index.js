const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    period: 'week',
    summary: {
      total_amount: '0.00',
      order_count: 0,
      service_amount: '0.00',
      withdraw_amount: '0.00',
      available_amount: '0.00'
    },
    records: [],
    page: 1,
    pageSize: 20,
    hasMore: true,
    loading: false,
    refreshing: false
  },

  onLoad() {
    this.loadData();
  },

  onPullDownRefresh() {
    this.setData({ refreshing: true });
    this.loadData(true).then(() => {
      this.setData({ refreshing: false });
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadRecords();
    }
  },

  onPeriodChange(e) {
    const period = e.currentTarget.dataset.period;
    this.setData({
      period: period,
      page: 1,
      records: [],
      hasMore: true
    });
    this.loadData();
  },

  async loadData(refresh = false) {
    try {
      const [summaryRes, recordsRes] = await Promise.all([
        app.request({
          url: '/?c=merchant&a=incomeSummary',
          method: 'GET',
          data: {
            period: this.data.period
          }
        }),
        app.request({
          url: '/?c=merchant&a=incomeRecords',
          method: 'GET',
          data: {
            period: this.data.period,
            page: 1,
            pageSize: this.data.pageSize
          }
        })
      ]);

      const records = (recordsRes.data.list || []).map(item => ({
        ...item,
        create_time_text: util.formatTime(item.create_time)
      }));

      this.setData({
        summary: summaryRes.data || this.data.summary,
        records: records,
        page: 2,
        hasMore: records.length >= this.data.pageSize
      });
    } catch (e) {
      console.error(e);
    }
  },

  async loadRecords() {
    if (this.data.loading) return;

    this.setData({ loading: true });

    try {
      const res = await app.request({
        url: '/?c=merchant&a=incomeRecords',
        method: 'GET',
        data: {
          period: this.data.period,
          page: this.data.page,
          pageSize: this.data.pageSize
        }
      });

      const list = (res.data.list || []).map(item => ({
        ...item,
        create_time_text: util.formatTime(item.create_time)
      }));

      this.setData({
        records: [...this.data.records, ...list],
        page: this.data.page + 1,
        hasMore: list.length >= this.data.pageSize,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onWithdraw() {
    wx.navigateTo({
      url: '/pages/mine/withdraw/withdraw'
    });
  }
});
