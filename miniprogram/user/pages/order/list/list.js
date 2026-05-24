const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    tabs: [
      { status: '', label: '全部' },
      { status: 0, label: '待付款' },
      { status: 1, label: '待服务' },
      { status: 2, label: '服务中' },
      { status: 3, label: '已完成' }
    ],
    currentTab: '',
    orders: [],
    page: 1,
    pageSize: 10,
    total: 0,
    hasMore: true,
    loading: false
  },

  onLoad(options) {
    const status = options.status !== undefined ? options.status : '';
    this.setData({ currentTab: status });
    this.loadOrders(true);
  },

  onShow() {
    if (app.checkLogin()) {
      this.loadOrders(true);
    }
  },

  onPullDownRefresh() {
    this.loadOrders(true).then(() => {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadOrders(false);
    }
  },

  onTabTap(e) {
    const status = e.currentTarget.dataset.status;
    if (status === this.data.currentTab) return;

    this.setData({ currentTab: status });
    this.loadOrders(true);
  },

  async loadOrders(refresh = false) {
    if (!app.checkLogin()) {
      return;
    }

    if (this.data.loading) return;

    this.setData({ loading: true });
    app.showLoading();

    try {
      const page = refresh ? 1 : this.data.page;
      const res = await app.request({
        url: '/?c=order&a=list',
        method: 'GET',
        data: {
          status: this.data.currentTab,
          page: page,
          page_size: this.data.pageSize
        }
      });

      const newOrders = res.data.list || [];
      const orders = refresh ? newOrders : [...this.data.orders, ...newOrders];

      this.setData({
        orders,
        total: res.data.total || 0,
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

  onOrderTap(e) {
    const order = e.currentTarget.dataset.order;
    wx.navigateTo({
      url: `/pages/order/detail?id=${order.id}`
    });
  },

  onPay(e) {
    const order = e.currentTarget.dataset.order;
    wx.navigateTo({
      url: `/pages/pay/index?order_id=${order.id}`
    });
  },

  onCancel(e) {
    const order = e.currentTarget.dataset.order;
    wx.showModal({
      title: '取消订单',
      content: '确定要取消该订单吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            await app.request({
              url: '/?c=order&a=cancel',
              method: 'POST',
              data: {
                id: order.id,
                reason: '用户取消'
              }
            });
            app.showToast('取消成功');
            this.loadOrders(true);
          } catch (e) {
            console.error(e);
          }
        }
      }
    });
  },

  onReview(e) {
    const order = e.currentTarget.dataset.order;
    wx.navigateTo({
      url: `/pages/order/review?id=${order.id}`
    });
  },

  onContact(e) {
    const order = e.currentTarget.dataset.order;
    const phone = order.technician_mobile || order.merchant_mobile || '400-000-0000';
    wx.makePhoneCall({
      phoneNumber: phone,
      fail: () => {
        app.showToast('拨号失败');
      }
    });
  },

  onComplaint(e) {
    const order = e.currentTarget.dataset.order;
    wx.navigateTo({
      url: `/pages/complaint/index?order_id=${order.id}`
    });
  },

  onAddon(e) {
    const order = e.currentTarget.dataset.order;
    wx.navigateTo({
      url: `/pages/order/addon?id=${order.id}`
    });
  },

  formatTime(timestamp) {
    return util.formatDateTime(timestamp);
  }
});
