const app = getApp();

Page({
  data: {
    orderId: 0,
    orderInfo: null,
    supplementInfo: null,
    loading: true
  },

  onLoad(options) {
    this.setData({
      orderId: options.id || 0
    });
    this.loadSupplementInfo();
  },

  async loadSupplementInfo() {
    try {
      const res = await app.request({
        url: '/?c=order&a=supplementInfo',
        method: 'GET',
        data: {
          id: this.data.orderId
        }
      });
      this.setData({
        orderInfo: res.data.order,
        supplementInfo: res.data.supplement,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
      app.showToast(e.msg || '加载失败');
    }
  },

  async onPay() {
    try {
      wx.showLoading({ title: '支付中...' });
      
      const res = await app.request({
        url: '/?c=order&a=paySupplement',
        method: 'POST',
        data: {
          order_id: this.data.orderId,
          amount: this.data.supplementInfo.amount
        }
      });

      const payParams = res.data.pay_params;
      
      wx.requestPayment({
        timeStamp: payParams.timeStamp,
        nonceStr: payParams.nonceStr,
        package: payParams.package,
        signType: payParams.signType,
        paySign: payParams.paySign,
        success: () => {
          wx.hideLoading();
          app.showToast('支付成功');
          setTimeout(() => {
            wx.redirectTo({
              url: `/pages/order/detail/detail?id=${this.data.orderId}`
            });
          }, 1500);
        },
        fail: () => {
          wx.hideLoading();
          app.showToast('支付已取消');
        }
      });
    } catch (e) {
      wx.hideLoading();
      console.error(e);
      app.showToast(e.msg || '支付失败');
    }
  }
});
