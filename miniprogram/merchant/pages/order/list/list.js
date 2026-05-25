const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    tabs: [
      { id: 0, name: '全部', status: '' },
      { id: 1, name: '待付款', status: 0 },
      { id: 2, name: '待派单', status: 1 },
      { id: 3, name: '待服务', status: 3 },
      { id: 4, name: '服务中', status: 4 },
      { id: 5, name: '待核销', status: 9 },
      { id: 6, name: '已完成', status: 6 }
    ],
    activeTab: 0,
    orders: [],
    page: 1,
    pageSize: 10,
    hasMore: true,
    loading: false,
    refreshing: false
  },

  onLoad() {
    this.loadOrders();
  },

  onPullDownRefresh() {
    this.setData({ refreshing: true });
    this.loadOrders(true).then(() => {
      this.setData({ refreshing: false });
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadOrders();
    }
  },

  onTabChange(e) {
    const tabId = parseInt(e.currentTarget.dataset.id);
    this.setData({
      activeTab: tabId,
      page: 1,
      orders: [],
      hasMore: true
    });
    this.loadOrders();
  },

  async loadOrders(refresh = false) {
    if (this.data.loading && !refresh) return;

    const page = refresh ? 1 : this.data.page;
    const currentTab = this.data.tabs.find(t => t.id === this.data.activeTab);
    
    this.setData({ loading: true });

    try {
      const res = await app.request({
        url: '/?c=order&a=merchantList',
        method: 'GET',
        data: {
          status: currentTab.status,
          page: page,
          pageSize: this.data.pageSize
        }
      });

      const list = res.data.list || [];
      const orders = refresh ? list : [...this.data.orders, ...list];
      
      this.setData({
        orders: orders,
        page: page + 1,
        hasMore: list.length >= this.data.pageSize,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onOrderDetail(e) {
    const orderId = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: `/pages/order/detail/detail?id=${orderId}`
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
            this.loadOrders(true);
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  async onCancelOrder(e) {
    const orderId = e.currentTarget.dataset.id;
    
    wx.showModal({
      title: '取消订单',
      content: '确定要取消此订单吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            wx.showLoading({ title: '取消中...' });
            
            await app.request({
              url: '/?c=order&a=cancel',
              method: 'POST',
              data: {
                order_id: orderId
              }
            });

            wx.hideLoading();
            app.showToast('取消成功', 'success');
            this.loadOrders(true);
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  async onContactUser(e) {
    const phone = e.currentTarget.dataset.phone;
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
  }
});
