const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    currentTab: 0,
    tabs: ['可使用', '已使用', '已过期'],
    coupons: [],
    page: 1,
    pageSize: 20,
    hasMore: true,
    loading: false,
    showCodeInput: false,
    code: ''
  },

  onLoad() {
    this.loadCoupons();
  },

  onShow() {
    this.loadCoupons(true);
  },

  async loadCoupons(refresh = false) {
    if (this.data.loading) return;

    this.setData({ loading: true });
    app.showLoading();

    try {
      const page = refresh ? 1 : this.data.page;
      const status = this.data.currentTab === 0 ? 1 : (this.data.currentTab === 1 ? 2 : 3);
      
      const res = await app.request({
        url: '/?c=user&a=couponList',
        method: 'GET',
        data: {
          status: status,
          page: page,
          page_size: this.data.pageSize
        }
      });

      const newCoupons = res.data.list || [];
      const coupons = refresh ? newCoupons : [...this.data.coupons, ...newCoupons];

      this.setData({
        coupons,
        page: page + 1,
        hasMore: res.data.has_more || false,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    } finally {
      app.hideLoading();
    }
  },

  onTabTap(e) {
    const index = e.currentTarget.dataset.index;
    this.setData({
      currentTab: index,
      page: 1,
      hasMore: true,
      coupons: []
    });
    this.loadCoupons(true);
  },

  onUseCoupon(e) {
    const coupon = e.currentTarget.dataset.coupon;
    if (coupon.status !== 1) return;
    
    wx.switchTab({
      url: '/pages/index/index'
    });
  },

  onShowCodeInput() {
    this.setData({ showCodeInput: true });
  },

  hideCodeInput() {
    this.setData({ showCodeInput: false, code: '' });
  },

  onCodeInput(e) {
    this.setData({ code: e.detail.value });
  },

  async onRedeemCode() {
    if (!this.data.code.trim()) {
      app.showToast('请输入兑换码');
      return;
    }

    try {
      await app.request({
        url: '/?c=user&a=couponRedeem',
        method: 'POST',
        data: {
          code: this.data.code.trim()
        }
      });

      app.showToast('兑换成功');
      this.hideCodeInput();
      this.loadCoupons(true);
    } catch (e) {
      console.error(e);
    }
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadCoupons();
    }
  },

  formatTime(timestamp) {
    return util.formatDate(timestamp);
  }
});
