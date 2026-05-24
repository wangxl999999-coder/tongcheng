const app = getApp();

Page({
  data: {
    technicianInfo: null,
    stats: {
      totalOrders: 0,
      totalIncome: '0.00',
      rating: 5.0
    },
    menuList: [
      { id: 1, name: '个人信息', icon: '👤', url: '/pages/mine/info/info' },
      { id: 2, name: '服务设置', icon: '⚙️', url: '/pages/mine/settings/settings' },
      { id: 3, name: '收入明细', icon: '💰', url: '/pages/income/index' },
      { id: 4, name: '提现', icon: '💳', url: '/pages/mine/withdraw/withdraw' },
      { id: 5, name: '我的评价', icon: '⭐', url: '/pages/mine/reviews/reviews' },
      { id: 6, name: '联系客服', icon: '📞', url: '/pages/mine/service/service' },
      { id: 7, name: '关于我们', icon: 'ℹ️', url: '/pages/mine/about/about' },
      { id: 8, name: '用户协议', icon: '📄', url: '/pages/mine/agreement/agreement' },
      { id: 9, name: '隐私政策', icon: '🔒', url: '/pages/mine/privacy/privacy' }
    ]
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
  },

  async loadData() {
    try {
      const res = await app.request({
        url: '/?c=technician&a=mineStats',
        method: 'GET'
      });

      this.setData({
        technicianInfo: res.data.technician || app.globalData.technicianInfo,
        stats: res.data.stats || this.data.stats
      });
    } catch (e) {
      console.error(e);
    }
  },

  onMenuTap(e) {
    const url = e.currentTarget.dataset.url;
    if (url) {
      if (url.startsWith('/pages/income')) {
        wx.switchTab({ url });
      } else {
        wx.navigateTo({ url });
      }
    }
  },

  onEditInfo() {
    wx.navigateTo({
      url: '/pages/mine/info/info'
    });
  },

  onLogout() {
    wx.showModal({
      title: '退出登录',
      content: '确定要退出登录吗？',
      success: (res) => {
        if (res.confirm) {
          wx.removeStorageSync('technician_token');
          wx.removeStorageSync('technician_info');
          app.globalData.token = '';
          app.globalData.technicianInfo = null;
          
          wx.reLaunch({
            url: '/pages/login/login'
          });
        }
      }
    });
  },

  onToggleOnline() {
    const newStatus = this.data.technicianInfo.online_status === 1 ? 0 : 1;
    
    app.request({
      url: '/?c=technician&a=setOnline',
      method: 'POST',
      data: {
        status: newStatus
      }
    }).then(() => {
      const technicianInfo = { ...this.data.technicianInfo };
      technicianInfo.online_status = newStatus;
      this.setData({ technicianInfo });
      app.globalData.onlineStatus = newStatus;
      app.showToast(newStatus === 1 ? '已上线' : '已下线');
    }).catch((e) => {
      console.error(e);
    });
  }
});
