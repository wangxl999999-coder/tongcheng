const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    orderId: '',
    order: null,
    loading: true,
    timeline: []
  },

  onLoad(options) {
    this.setData({
      orderId: options.id
    });
    this.loadOrderDetail();
  },

  async loadOrderDetail() {
    try {
      const res = await app.request({
        url: '/?c=order&a=detail',
        method: 'GET',
        data: {
          id: this.data.orderId
        }
      });

      const order = res.data;
      order.status_text = util.getStatusText(order.status);
      order.status_color = util.getStatusColor(order.status);
      order.create_time = util.formatTime(order.create_time);
      order.appointment_time = util.formatTime(order.appointment_time);

      const timeline = this.buildTimeline(order);

      this.setData({
        order: order,
        timeline: timeline,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  buildTimeline(order) {
    const timeline = [];
    
    if (order.create_time) {
      timeline.push({
        title: '订单创建',
        time: order.create_time,
        done: true,
        first: true
      });
    }
    
    if (order.pay_time) {
      timeline.push({
        title: '支付成功',
        time: util.formatTime(order.pay_time),
        done: true
      });
    }
    
    if (order.dispatch_time) {
      timeline.push({
        title: '已派单',
        time: util.formatTime(order.dispatch_time),
        done: true
      });
    }
    
    if (order.accept_time) {
      timeline.push({
        title: '技师已接单',
        time: util.formatTime(order.accept_time),
        done: true
      });
    }
    
    if (order.start_time) {
      timeline.push({
        title: '开始服务',
        time: util.formatTime(order.start_time),
        done: true
      });
    }
    
    if (order.finish_time) {
      timeline.push({
        title: '服务完成',
        time: util.formatTime(order.finish_time),
        done: true
      });
    }
    
    if (order.cancel_time) {
      timeline.push({
        title: '订单已取消',
        time: util.formatTime(order.cancel_time),
        done: true
      });
    }

    return timeline;
  },

  async onDispatchOrder() {
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
                order_id: this.data.orderId
              }
            });

            wx.hideLoading();
            app.showToast('派单成功', 'success');
            this.loadOrderDetail();
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  async onCancelOrder() {
    wx.showModal({
      title: '取消订单',
      content: '确定要取消此订单吗？取消后无法恢复。',
      success: async (res) => {
        if (res.confirm) {
          try {
            wx.showLoading({ title: '取消中...' });
            
            await app.request({
              url: '/?c=order&a=cancel',
              method: 'POST',
              data: {
                order_id: this.data.orderId
              }
            });

            wx.hideLoading();
            app.showToast('取消成功', 'success');
            this.loadOrderDetail();
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  onContactUser() {
    const phone = this.data.order.user_phone;
    if (!phone) return;
    
    wx.makePhoneCall({
      phoneNumber: phone,
      fail: () => {
        wx.setClipboardData({
          data: phone,
          success: () => {
            app.showToast('手机号已复制');
          }
        });
      }
    });
  },

  onContactTechnician() {
    const phone = this.data.order.technician_phone;
    if (!phone) return;
    
    wx.makePhoneCall({
      phoneNumber: phone,
      fail: () => {
        wx.setClipboardData({
          data: phone,
          success: () => {
            app.showToast('手机号已复制');
          }
        });
      }
    });
  },

  onCopyOrderNo() {
    wx.setClipboardData({
      data: this.data.order.order_no,
      success: () => {
        app.showToast('订单号已复制');
      }
    });
  }
});
