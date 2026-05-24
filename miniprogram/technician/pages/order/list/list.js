const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    tabs: [
      { id: 0, name: '全部' },
      { id: 2, name: '待接单' },
      { id: 3, name: '待服务' },
      { id: 4, name: '服务中' },
      { id: 6, name: '已完成' }
    ],
    activeTab: 0,
    orderList: [],
    page: 1,
    pageSize: 10,
    hasMore: true,
    loading: true,
    loadingMore: false
  },

  onLoad() {
    this.loadOrders();
  },

  onShow() {
    this.loadOrders(true);
  },

  onPullDownRefresh() {
    this.loadOrders(true).then(() => {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loadingMore) {
      this.loadMore();
    }
  },

  onTabChange(e) {
    const id = parseInt(e.currentTarget.dataset.id);
    this.setData({
      activeTab: id,
      page: 1,
      hasMore: true,
      orderList: []
    });
    this.loadOrders();
  },

  async loadOrders(refresh = false) {
    if (!refresh) {
      this.setData({ loading: true });
    }

    try {
      const res = await app.request({
        url: '/?c=order&a=technicianList',
        method: 'GET',
        data: {
          status: this.data.activeTab === 0 ? '' : this.data.activeTab,
          page: 1,
          pageSize: this.data.pageSize
        }
      });

      const list = res.data.list || [];
      
      this.setData({
        orderList: list,
        hasMore: list.length >= this.data.pageSize,
        page: 2,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  async loadMore() {
    this.setData({ loadingMore: true });

    try {
      const res = await app.request({
        url: '/?c=order&a=technicianList',
        method: 'GET',
        data: {
          status: this.data.activeTab === 0 ? '' : this.data.activeTab,
          page: this.data.page,
          pageSize: this.data.pageSize
        }
      });

      const list = res.data.list || [];
      
      this.setData({
        orderList: [...this.data.orderList, ...list],
        hasMore: list.length >= this.data.pageSize,
        page: this.data.page + 1,
        loadingMore: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loadingMore: false });
    }
  },

  onOrderDetail(e) {
    const orderId = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: `/pages/order/detail/detail?id=${orderId}`
    });
  },

  async onAcceptOrder(e) {
    const orderId = e.currentTarget.dataset.id;
    
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
                order_id: orderId
              }
            });

            wx.hideLoading();
            app.showToast('接单成功', 'success');
            this.loadOrders(true);
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  async onStartService(e) {
    const orderId = e.currentTarget.dataset.id;
    
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
                order_id: orderId
              }
            });

            wx.hideLoading();
            app.showToast('服务已开始', 'success');
            this.loadOrders(true);
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  async onCompleteService(e) {
    const orderId = e.currentTarget.dataset.id;
    
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
                order_id: orderId
              }
            });

            wx.hideLoading();
            app.showToast('服务已完成', 'success');
            this.loadOrders(true);
          } catch (e) {
            wx.hideLoading();
            console.error(e);
          }
        }
      }
    });
  },

  onCallUser(e) {
    const phone = e.currentTarget.dataset.phone;
    wx.makePhoneCall({
      phoneNumber: phone,
      fail: () => {
        app.showToast('拨号失败');
      }
    });
  },

  onNavigate(e) {
    const address = e.currentTarget.dataset.address;
    wx.openLocation({
      name: '服务地址',
      address: address,
      fail: () => {
        app.showToast('导航失败');
      }
    });
  }
});
