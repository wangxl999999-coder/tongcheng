const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    orderId: 0,
    orderInfo: null,
    loading: true
  },

  onLoad(options) {
    this.setData({
      orderId: options.id || 0
    });
    this.loadOrderDetail();
  },

  onShow() {
    if (this.data.orderId) {
      this.loadOrderDetail();
    }
  },

  async loadOrderDetail() {
    try {
      const res = await app.request({
        url: '/?c=order&a=technicianDetail',
        method: 'GET',
        data: {
          id: this.data.orderId
        }
      });

      this.setData({
        orderInfo: res.data,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  async onAcceptOrder() {
    wx.showModal({
      title: '确认接单',
      content: '确定要接受这个订单吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            wx.showLoading({ title: '接单中...' });
            
            await app.request({
              url: '/?c=order&a=accept',
              method: 'POST',
              data: {
                order_id: this.data.orderId
              }
            });

            wx.hideLoading();
            app.showToast('接单成功', 'success');
            this.loadOrderDetail();
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  async onStartService() {
    wx.showModal({
      title: '开始服务',
      content: '确定要开始服务吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            wx.showLoading({ title: '处理中...' });
            
            await app.request({
              url: '/?c=order&a=startService',
              method: 'POST',
              data: {
                order_id: this.data.orderId
              }
            });

            wx.hideLoading();
            app.showToast('服务已开始', 'success');
            this.loadOrderDetail();
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  async onCompleteService() {
    wx.showModal({
      title: '完成服务',
      content: '确定服务已完成吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            wx.showLoading({ title: '处理中...' });
            
            await app.request({
              url: '/?c=order&a=completeService',
              method: 'POST',
              data: {
                order_id: this.data.orderId
              }
            });

            wx.hideLoading();
            app.showToast('服务已完成', 'success');
            this.loadOrderDetail();
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  onCallUser() {
    if (this.data.orderInfo && this.data.orderInfo.user_phone) {
      wx.makePhoneCall({
        phoneNumber: this.data.orderInfo.user_phone,
        fail: () => {
          app.showToast('拨号失败');
        }
      });
    }
  },

  onNavigate() {
    if (this.data.orderInfo && this.data.orderInfo.address) {
      wx.openLocation({
        name: '服务地址',
        address: this.data.orderInfo.address,
        latitude: parseFloat(this.data.orderInfo.latitude) || 0,
        longitude: parseFloat(this.data.orderInfo.longitude) || 0,
        fail: () => {
          app.showToast('导航失败');
        }
      });
    }
  },

  onAddon() {
    wx.navigateTo({
      url: `/pages/order/addon/addon?id=${this.data.orderId}`
    });
  },

  onSupplement() {
    wx.navigateTo({
      url: `/pages/order/supplement/supplement?id=${this.data.orderId}`
    });
  },

  onCopyOrderNo() {
    if (this.data.orderInfo && this.data.orderInfo.order_no) {
      wx.setClipboardData({
        data: this.data.orderInfo.order_no,
        success: () => {
          app.showToast('订单号已复制');
        }
      });
    }
  }
});
