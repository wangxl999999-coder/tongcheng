const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    technicianInfo: null,
    onlineStatus: 0,
    stats: {
      todayOrders: 0,
      todayIncome: '0.00',
      pendingOrders: 0
    },
    grabOrders: [],
    loading: true,
    refreshing: false
  },

  onLoad() {
    this.loadData();
  },

  onShow() {
    if (app.globalData.technicianInfo) {
      this.setData({
        technicianInfo: app.globalData.technicianInfo
      });
    }
    this.loadData();
    this.startPolling();
  },

  onHide() {
    this.stopPolling();
  },

  onUnload() {
    this.stopPolling();
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
          url: '/?c=technician&a=stats',
          method: 'GET'
        }),
        app.request({
          url: '/?c=order&a=grabList',
          method: 'GET'
        })
      ]);

      this.setData({
        stats: statsRes.data || this.data.stats,
        grabOrders: ordersRes.data || [],
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  startPolling() {
    this.stopPolling();
    this.pollingTimer = setInterval(() => {
      this.loadGrabOrders();
    }, 10000);
  },

  stopPolling() {
    if (this.pollingTimer) {
      clearInterval(this.pollingTimer);
      this.pollingTimer = null;
    }
  },

  async loadGrabOrders() {
    try {
      const res = await app.request({
        url: '/?c=order&a=grabList',
        method: 'GET'
      });
      this.setData({
        grabOrders: res.data || []
      });
    } catch (e) {
      console.error(e);
    }
  },

  async onToggleOnline() {
    const newStatus = this.data.onlineStatus === 1 ? 0 : 1;
    
    try {
      await app.request({
        url: '/?c=technician&a=setOnline',
        method: 'POST',
        data: {
          status: newStatus
        }
      });

      this.setData({
        onlineStatus: newStatus
      });

      app.globalData.onlineStatus = newStatus;
      
      app.showToast(newStatus === 1 ? '已上线' : '已下线');
    } catch (e) {
      console.error(e);
    }
  },

  async onGrabOrder(e) {
    const orderId = e.currentTarget.dataset.id;

    wx.showModal({
      title: '确认抢单',
      content: '确定要抢这个订单吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            wx.showLoading({ title: '抢单中...' });
            
            await app.request({
              url: '/?c=order&a=grab',
              method: 'POST',
              data: {
                order_id: orderId
              }
            });

            wx.hideLoading();
            app.showToast('抢单成功', 'success');
            
            setTimeout(() => {
              wx.navigateTo({
                url: `/pages/order/detail/detail?id=${orderId}`
              });
            }, 1500);
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  onOrderDetail(e) {
    const orderId = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: `/pages/order/detail/detail?id=${orderId}`
    });
  },

  onRefreshOrders() {
    wx.showLoading({ title: '刷新中...' });
    this.loadGrabOrders().then(() => {
      wx.hideLoading();
      app.showToast('刷新成功', 'success');
    });
  }
});
