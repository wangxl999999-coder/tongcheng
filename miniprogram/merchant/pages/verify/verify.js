const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    orderNo: '',
    scanResult: null,
    verifying: false,
    recentList: [],
    tabActive: 0
  },

  onLoad() {
    this.loadRecentList();
  },

  onShow() {
    if (this.data.tabActive === 1) {
      this.loadRecentList();
    }
  },

  onTabChange(e) {
    const active = parseInt(e.currentTarget.dataset.active);
    this.setData({
      tabActive: active
    });
    if (active === 1) {
      this.loadRecentList();
    }
  },

  onOrderNoInput(e) {
    this.setData({
      orderNo: e.detail.value
    });
  },

  async loadRecentList() {
    try {
      const res = await app.request({
        url: '/?c=order&a=verifyList',
        method: 'GET',
        data: {
          page: 1,
          pageSize: 10
        }
      });

      const list = (res.data.list || []).map(item => {
        return {
          ...item,
          verify_time_text: util.formatTime(item.verify_time),
          status_text: util.getStatusText(item.status),
          status_color: util.getStatusColor(item.status)
        };
      });

      this.setData({
        recentList: list
      });
    } catch (e) {
      console.error(e);
    }
  },

  async onScanCode() {
    try {
      const res = await wx.scanCode({
        onlyFromCamera: false,
        scanType: ['qrCode', 'barCode']
      });

      this.setData({
        orderNo: res.result
      });

      await this.queryOrder(res.result);
    } catch (e) {
      if (e.errMsg !== 'scanCode:fail cancel') {
        app.showToast('扫码失败');
      }
    }
  },

  async onQuery() {
    if (!this.data.orderNo) {
      app.showToast('请输入订单号');
      return;
    }

    await this.queryOrder(this.data.orderNo);
  },

  async queryOrder(orderNo) {
    try {
      wx.showLoading({ title: '查询中...' });
      
      const res = await app.request({
        url: '/?c=order&a=queryByNo',
        method: 'GET',
        data: {
          order_no: orderNo
        }
      });

      wx.hideLoading();

      const order = res.data;
      if (order.status !== 9) {
        app.showToast('订单状态不正确，无法核销');
        return;
      }

      order.status_text = util.getStatusText(order.status);
      order.status_color = util.getStatusColor(order.status);
      order.appointment_time = util.formatTime(order.appointment_time);

      this.setData({
        scanResult: order
      });
    } catch (e) {
      wx.hideLoading();
      console.error(e);
      app.showToast(e.msg || '订单不存在');
    }
  },

  async onVerify() {
    if (!this.data.scanResult) return;

    wx.showModal({
      title: '确认核销',
      content: `确定核销订单 ${this.data.scanResult.order_no}？`,
      success: async (res) => {
        if (res.confirm) {
          try {
            this.setData({ verifying: true });
            wx.showLoading({ title: '核销中...' });
            
            await app.request({
              url: '/?c=order&a=verify',
              method: 'POST',
              data: {
                order_id: this.data.scanResult.id
              }
            });

            wx.hideLoading();
            this.setData({ verifying: false });
            
            app.showToast('核销成功', 'success');
            
            this.setData({
              scanResult: null,
              orderNo: ''
            });
            
            this.loadRecentList();
          } catch (e) {
            wx.hideLoading();
            this.setData({ verifying: false });
            console.error(e);
          }
        }
      }
    });
  },

  onClearResult() {
    this.setData({
      scanResult: null,
      orderNo: ''
    });
  },

  onOrderDetail(e) {
    const orderId = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: `/pages/order/detail/detail?id=${orderId}`
    });
  }
});
