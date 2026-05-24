const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    balance: 0,
    totalRecharge: 0,
    totalConsume: 0,
    currentTab: 0,
    tabs: ['全部', '充值', '消费', '退款'],
    records: [],
    page: 1,
    pageSize: 20,
    hasMore: true,
    loading: false
  },

  onLoad() {
    this.loadBalance();
    this.loadRecords();
  },

  onShow() {
    this.loadBalance();
    this.loadRecords(true);
  },

  async loadBalance() {
    try {
      const res = await app.request({
        url: '/?c=user&a=walletInfo',
        method: 'GET'
      });

      this.setData({
        balance: res.data.balance || 0,
        totalRecharge: res.data.total_recharge || 0,
        totalConsume: res.data.total_consume || 0
      });
    } catch (e) {
      console.error(e);
    }
  },

  async loadRecords(refresh = false) {
    if (this.data.loading) return;
    
    this.setData({ loading: true });
    app.showLoading();

    try {
      const page = refresh ? 1 : this.data.page;
      const res = await app.request({
        url: '/?c=user&a=walletRecords',
        method: 'GET',
        data: {
          type: this.data.currentTab,
          page: page,
          page_size: this.data.pageSize
        }
      });

      const newRecords = res.data.list || [];
      const records = refresh ? newRecords : [...this.data.records, ...newRecords];

      this.setData({
        records,
        page: page + 1,
        hasMore: res.data.has_more || false,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    } finally {
      app.hideLoading();
    }
  },

  onTabTap(e) {
    const index = e.currentTarget.dataset.index;
    this.setData({
      currentTab: index,
      page: 1,
      hasMore: true,
      records: []
    });
    this.loadRecords(true);
  },

  onRecharge() {
    wx.navigateTo({
      url: '/pages/mine/recharge'
    });
  },

  onWithdraw() {
    app.showToast('暂未开放');
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadRecords();
    }
  },

  formatTime(timestamp) {
    return util.formatDateTime(timestamp);
  },

  formatMoney(amount) {
    return util.formatMoney(amount);
  }
});
