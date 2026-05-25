const app = getApp();

Page({
  data: {
    merchantInfo: null,
    storeInfo: null,
    menuList: [
      [
        { icon: '🏪', name: '门店管理', url: '/pages/store/info/info' },
        { icon: '👥', name: '技师管理', url: '/pages/mine/technician/technician' },
        { icon: '📋', name: '服务项目', url: '/pages/mine/service/service' },
        { icon: '📊', name: '数据统计', url: '/pages/mine/statistics/statistics' }
      ],
      [
        { icon: '💰', name: '余额提现', url: '/pages/mine/withdraw/withdraw' },
        { icon: '📄', name: '提现记录', url: '/pages/mine/withdraw-list/withdraw-list' },
        { icon: '🎫', name: '优惠券', url: '/pages/mine/coupon/coupon' }
      ],
      [
        { icon: '❓', name: '帮助中心', url: '/pages/mine/help/help' },
        { icon: '📞', name: '联系客服', url: '/pages/mine/service/service' },
        { icon: '⚙️', name: '设置', url: '/pages/mine/settings/settings' }
      ]
    ]
  },

  onShow() {
    this.setData({
      merchantInfo: app.globalData.merchantInfo,
      storeInfo: app.globalData.storeInfo
    });
  },

  onMenuTap(e) {
    const url = e.currentTarget.dataset.url;
    if (!url) return;
    
    wx.navigateTo({
      url: url,
      fail: () => {
        app.showToast('功能开发中');
      }
    });
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
          wx.removeStorageSync('merchant_token');
          wx.removeStorageSync('merchant_info');
          wx.removeStorageSync('store_info');
          app.globalData.token = '';
          app.globalData.merchantInfo = null;
          app.globalData.storeInfo = null;
          
          wx.reLaunch({
            url: '/pages/login/login'
          });
        }
      }
    });
  },

  onContactService() {
    wx.navigateTo({
      url: '/pages/mine/service/service'
    });
  }
});
