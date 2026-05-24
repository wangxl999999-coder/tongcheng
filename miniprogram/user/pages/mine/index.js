const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    userInfo: null,
    isLogin: false,
    orderStatistics: {
      pending: 0,
      unpaid: 0,
      servicing: 0,
      completed: 0,
      cancelled: 0
    },
    menuList: [
      [
        { icon: '/images/menu-wallet.png', text: '我的钱包', url: '/pages/mine/wallet' },
        { icon: '/images/menu-coupon.png', text: '优惠券', url: '/pages/mine/coupon' },
        { icon: '/images/menu-package.png', text: '套餐卡', url: '/pages/mine/package' },
        { icon: '/images/menu-member.png', text: '会员中心', url: '/pages/mine/member' }
      ],
      [
        { icon: '/images/menu-address.png', text: '地址管理', url: '/pages/mine/address' },
        { icon: '/images/menu-invoice.png', text: '发票管理', url: '/pages/mine/invoice' },
        { icon: '/images/menu-collect.png', text: '我的收藏', url: '/pages/mine/collect' },
        { icon: '/images/menu-history.png', text: '浏览历史', url: '/pages/mine/history' }
      ],
      [
        { icon: '/images/menu-help.png', text: '帮助中心', url: '/pages/mine/help' },
        { icon: '/images/menu-service.png', text: '联系客服', url: '/pages/mine/service' },
        { icon: '/images/menu-about.png', text: '关于我们', url: '/pages/mine/about' },
        { icon: '/images/menu-setting.png', text: '设置', url: '/pages/mine/setting' }
      ]
    ]
  },

  onLoad() {
    this.initData();
  },

  onShow() {
    if (app.checkLogin()) {
      this.loadUserInfo();
      this.loadOrderStatistics();
    } else {
      this.setData({
        isLogin: false,
        userInfo: null
      });
    }
  },

  initData() {
    if (app.checkLogin()) {
      this.setData({ isLogin: true });
      this.loadUserInfo();
      this.loadOrderStatistics();
    }
  },

  async loadUserInfo() {
    try {
      const res = await app.request({
        url: '/?c=user&a=info',
        method: 'GET'
      });

      this.setData({
        userInfo: res.data,
        isLogin: true
      });

      app.globalData.userInfo = res.data;
      wx.setStorageSync('userInfo', res.data);
    } catch (e) {
      console.error(e);
    }
  },

  async loadOrderStatistics() {
    try {
      const [pending, unpaid, servicing, completed, cancelled] = await Promise.all([
        app.request({ url: '/?c=order&a=list', data: { status: 1, page_size: 1 } }),
        app.request({ url: '/?c=order&a=list', data: { status: 0, page_size: 1 } }),
        app.request({ url: '/?c=order&a=list', data: { status: 2, page_size: 1 } }),
        app.request({ url: '/?c=order&a=list', data: { status: 3, page_size: 1 } }),
        app.request({ url: '/?c=order&a=list', data: { status: 7, page_size: 1 } })
      ]);

      this.setData({
        orderStatistics: {
          pending: pending.data.total || 0,
          unpaid: unpaid.data.total || 0,
          servicing: servicing.data.total || 0,
          completed: completed.data.total || 0,
          cancelled: cancelled.data.total || 0
        }
      });
    } catch (e) {
      console.error(e);
    }
  },

  async onLogin() {
    try {
      await app.login();
      this.setData({ isLogin: true });
      this.loadUserInfo();
      this.loadOrderStatistics();
    } catch (e) {
      console.error(e);
      app.showToast('登录失败');
    }
  },

  onEditProfile() {
    if (!this.checkLogin()) return;
    wx.navigateTo({
      url: '/pages/mine/info'
    });
  },

  onOrderTap(e) {
    if (!this.checkLogin()) return;
    const status = e.currentTarget.dataset.status;
    wx.navigateTo({
      url: `/pages/order/list?status=${status}`
    });
  },

  onMenuTap(e) {
    if (!this.checkLogin()) return;
    const menu = e.currentTarget.dataset.menu;
    wx.navigateTo({
      url: menu.url
    });
  },

  onRecharge() {
    if (!this.checkLogin()) return;
    wx.navigateTo({
      url: '/pages/mine/recharge'
    });
  },

  checkLogin() {
    if (!app.checkLogin()) {
      wx.showModal({
        title: '提示',
        content: '请先登录',
        confirmText: '去登录',
        success: (res) => {
          if (res.confirm) {
            this.onLogin();
          }
        }
      });
      return false;
    }
    return true;
  },

  formatMoney(amount) {
    return util.formatMoney(amount);
  }
});
