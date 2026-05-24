const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    currentTab: 0,
    tabs: ['可使用', '已用完', '已过期'],
    packages: [],
    page: 1,
    pageSize: 20,
    hasMore: true,
    loading: false,
    showDetail: false,
    currentPackage: null
  },

  onLoad() {
    this.loadPackages();
  },

  onShow() {
    this.loadPackages(true);
  },

  async loadPackages(refresh = false) {
    if (this.data.loading) return;

    this.setData({ loading: true });
    app.showLoading();

    try {
      const page = refresh ? 1 : this.data.page;
      const status = this.data.currentTab === 0 ? 1 : (this.data.currentTab === 1 ? 2 : 3);
      
      const res = await app.request({
        url: '/?c=user&a=packageList',
        method: 'GET',
        data: {
          status: status,
          page: page,
          page_size: this.data.pageSize
        }
      });

      const newPackages = res.data.list || [];
      const packages = refresh ? newPackages : [...this.data.packages, ...newPackages];

      this.setData({
        packages,
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
      packages: []
    });
    this.loadPackages(true);
  },

  onShowDetail(e) {
    const pkg = e.currentTarget.dataset.pkg;
    this.setData({
      showDetail: true,
      currentPackage: pkg
    });
  },

  hideDetail() {
    this.setData({ showDetail: false, currentPackage: null });
  },

  onUsePackage(e) {
    const pkg = e.currentTarget.dataset.pkg;
    if (pkg.remaining_times <= 0) {
      app.showToast('次数已用完');
      return;
    }

    wx.navigateTo({
      url: `/pages/service/detail?id=${pkg.service_id}`
    });
  },

  onRefund(e) {
    const pkg = e.currentTarget.dataset.pkg;
    wx.showModal({
      title: '申请退款',
      content: `确定要申请退款吗？退款金额 ¥${((pkg.price / pkg.total_times) * pkg.remaining_times).toFixed(2)}`,
      success: async (res) => {
        if (res.confirm) {
          try {
            await app.request({
              url: '/?c=user&a=packageRefund',
              method: 'POST',
              data: {
                id: pkg.id,
                reason: '用户申请退款'
              }
            });
            app.showToast('申请已提交');
            this.loadPackages(true);
            this.hideDetail();
          } catch (e) {
            console.error(e);
          }
        }
      }
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadPackages();
    }
  },

  formatTime(timestamp) {
    return util.formatDate(timestamp);
  },

  formatMoney(amount) {
    return util.formatMoney(amount);
  }
});
