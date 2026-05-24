const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    orderId: 0,
    order: null,
    loading: true
  },

  onLoad(options) {
    this.setData({
      orderId: parseInt(options.id) || 0
    });
    this.loadDetail();
  },

  async loadDetail() {
    try {
      const res = await app.request({
        url: '/?c=order&a=detail',
        method: 'GET',
        data: {
          id: this.data.orderId
        }
      });

      this.setData({
        order: res.data,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onPay() {
    const order = this.data.order;
    wx.navigateTo({
      url: `/pages/pay/index?order_id=${order.id}`
    });
  },

  onCancel() {
    const order = this.data.order;
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
            this.loadDetail();
          } catch (e) {
            console.error(e);
          }
        }
      }
    });
  },

  onReview() {
    const order = this.data.order;
    wx.navigateTo({
      url: `/pages/order/review?id=${order.id}`
    });
  },

  onAddon() {
    const order = this.data.order;
    wx.navigateTo({
      url: `/pages/order/addon?id=${order.id}`
    });
  },

  onContact() {
    const order = this.data.order;
    const phone = order.technician_mobile || order.merchant_mobile || '400-000-0000';
    wx.makePhoneCall({
      phoneNumber: phone,
      fail: () => {
        app.showToast('拨号失败');
      }
    });
  },

  onComplaint() {
    const order = this.data.order;
    wx.navigateTo({
      url: `/pages/complaint/index?order_id=${order.id}`
    });
  },

  onServiceTap() {
    const order = this.data.order;
    wx.navigateTo({
      url: `/pages/service/detail?id=${order.service_id}`
    });
  },

  onTechnicianTap() {
    const order = this.data.order;
    if (order.technician_id) {
      wx.navigateTo({
        url: `/pages/service/technician?id=${order.technician_id}`
      });
    }
  },

  onDispatch() {
    const order = this.data.order;
    wx.navigateTo({
      url: `/pages/order/dispatch?id=${order.id}`
    });
  },

  formatTime(timestamp) {
    return util.formatDateTime(timestamp);
  },

  formatMoney(amount) {
    return util.formatMoney(amount);
  }
});
