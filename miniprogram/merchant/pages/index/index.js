const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    merchantInfo: null,
    storeInfo: null,
    stats: {
      todayOrders: 0,
      todayAmount: '0.00',
      pendingOrders: 0,
      pendingVerify: 0,
      weekOrders: 0,
      weekAmount: '0.00'
    },
    recentOrders: [],
    loading: true,
    refreshing: false
  },

  onLoad() {
    this.loadData();
  },

  onShow() {
    if (app.globalData.merchantInfo) {
      this.setData({
        merchantInfo: app.globalData.merchantInfo,
        storeInfo: app.globalData.storeInfo
      });
    }
    this.loadData();
  },

  onPullDownRefresh() {
    this.setData({ refreshing: true });
    this.loadData().then(() => {
      this.setData({ refreshing: false });
      wx.stopPullDownRefresh();
    });
  },

  async loadData() {
    try {
      const [statsRes, ordersRes] = await Promise.all([
        app.request({
          url: '/?c=merchant&a=stats',
          method: 'GET'
        }),
        app.request({
          url: '/?c=order&a=merchantList',
          method: 'GET',
          data: {
            page: 1,
            pageSize: 5
          }
        })
      ]);

      this.setData({
        stats: statsRes.data || this.data.stats,
        recentOrders: ordersRes.data.list || [],
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onViewAllOrders() {
    wx.switchTab({
      url: '/pages/order/list'
    });
  },

  onOrderDetail(e) {
    const orderId = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: `/pages/order/detail/detail?id=${orderId}`
    });
  },

  onVerify() {
    wx.switchTab({
      url: '/pages/verify/verify'
    });
  },

  onIncome() {
    wx.switchTab({
      url: '/pages/income/index'
    });
  },

  onStoreInfo() {
    wx.navigateTo({
      url: '/pages/store/info'
    });
  },

  async onDispatchOrder(e) {
    const orderId = e.currentTarget.dataset.id;
    
    wx.showModal({
      title: '确认派单',
      content: '确定要将此订单派给指定技师吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            wx.showLoading({ title: '派单中...' });
            
            await app.request({
              url: '/?c=order&a=dispatch',
              method: 'POST',
              data: {
                order_id: orderId
              }
            });

            wx.hideLoading();
            app.showToast('派单成功', 'success');
            this.loadData();
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  }
});
