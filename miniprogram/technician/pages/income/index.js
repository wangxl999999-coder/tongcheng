const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    tabs: [
      { id: 'all', name: '全部' },
      { id: 'today', name: '今日' },
      { id: 'week', name: '本周' },
      { id: 'month', name: '本月' }
    ],
    activeTab: 'all',
    stats: {
      totalIncome: '0.00',
      todayIncome: '0.00',
      weekIncome: '0.00',
      monthIncome: '0.00',
      orderCount: 0,
      withdrawAmount: '0.00',
      availableAmount: '0.00'
    },
    incomeList: [],
    page: 1,
    pageSize: 10,
    hasMore: true,
    loading: true,
    loadingMore: false
  },

  onLoad() {
    this.loadData();
  },

  onShow() {
    this.loadData(true);
  },

  onPullDownRefresh() {
    this.loadData(true).then(() => {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loadingMore) {
      this.loadMore();
    }
  },

  onTabChange(e) {
    const id = e.currentTarget.dataset.id;
    this.setData({
      activeTab: id,
      page: 1,
      hasMore: true,
      incomeList: []
    });
    this.loadIncomeList();
  },

  async loadData(refresh = false) {
    if (!refresh) {
      this.setData({ loading: true });
    }

    try {
      const [statsRes, listRes] = await Promise.all([
        app.request({
          url: '/?c=technician&a=incomeStats',
          method: 'GET'
        }),
        app.request({
          url: '/?c=technician&a=incomeList',
          method: 'GET',
          data: {
            type: this.data.activeTab,
            page: 1,
            pageSize: this.data.pageSize
          }
        })
      ]);

      const list = listRes.data.list || [];

      this.setData({
        stats: statsRes.data || this.data.stats,
        incomeList: list,
        hasMore: list.length >= this.data.pageSize,
        page: 2,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  async loadIncomeList() {
    try {
      const res = await app.request({
        url: '/?c=technician&a=incomeList',
        method: 'GET',
        data: {
          type: this.data.activeTab,
          page: 1,
          pageSize: this.data.pageSize
        }
      });

      const list = res.data.list || [];

      this.setData({
        incomeList: list,
        hasMore: list.length >= this.data.pageSize,
        page: 2
      });
    } catch (e) {
      console.error(e);
    }
  },

  async loadMore() {
    this.setData({ loadingMore: true });

    try {
      const res = await app.request({
        url: '/?c=technician&a=incomeList',
        method: 'GET',
        data: {
          type: this.data.activeTab,
          page: this.data.page,
          pageSize: this.data.pageSize
        }
      });

      const list = res.data.list || [];

      this.setData({
        incomeList: [...this.data.incomeList, ...list],
        hasMore: list.length >= this.data.pageSize,
        page: this.data.page + 1,
        loadingMore: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loadingMore: false });
    }
  },

  onWithdraw() {
    wx.navigateTo({
      url: '/pages/mine/withdraw/withdraw'
    });
  },

  onIncomeDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: `/pages/order/detail/detail?id=${id}`
    });
  }
});
