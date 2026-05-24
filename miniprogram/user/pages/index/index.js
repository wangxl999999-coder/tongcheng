const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    cityId: 0,
    cityName: '选择城市',
    banners: [],
    notices: [],
    popup: null,
    showPopup: false,
    categories: [],
    hotServices: [],
    recommendServices: [],
    nearbyTechnicians: [],
    currentBanner: 0,
    longitude: 0,
    latitude: 0,
    loading: true
  },

  onLoad() {
    this.initData();
  },

  onShow() {
    if (app.globalData.cityId != this.data.cityId) {
      this.setData({
        cityId: app.globalData.cityId,
        cityName: app.globalData.cityName
      });
      this.loadData();
    }
  },

  onPullDownRefresh() {
    this.loadData().then(() => {
      wx.stopPullDownRefresh();
    });
  },

  initData() {
    this.setData({
      cityId: app.globalData.cityId,
      cityName: app.globalData.cityName,
      longitude: app.globalData.longitude,
      latitude: app.globalData.latitude
    });
    this.loadData();
  },

  async loadData() {
    try {
      const res = await app.request({
        url: '/?c=index&a=index',
        method: 'GET',
        data: {
          city_id: this.data.cityId,
          longitude: this.data.longitude,
          latitude: this.data.latitude
        }
      });

      this.setData({
        banners: res.data.banners || [],
        notices: res.data.notices || [],
        popup: res.data.popup,
        showPopup: !!res.data.popup,
        categories: res.data.categories || [],
        hotServices: res.data.hot_services || [],
        recommendServices: res.data.recommend_services || [],
        nearbyTechnicians: res.data.nearby_technicians || [],
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onBannerChange(e) {
    this.setData({
      currentBanner: e.detail.current
    });
  },

  onBannerTap(e) {
    const banner = e.currentTarget.dataset.banner;
    if (banner && banner.link) {
      wx.navigateTo({
        url: banner.link,
        fail: () => {
          if (banner.link.indexOf('http') === 0) {
            wx.setClipboardData({
              data: banner.link,
              success: () => {
                app.showToast('链接已复制');
              }
            });
          }
        }
      });
    }
  },

  onChooseCity() {
    wx.navigateTo({
      url: '/pages/city/index'
    });
  },

  onSearch() {
    wx.navigateTo({
      url: '/pages/service/list?keyword=' + encodeURIComponent(this.data.keyword || '')
    });
  },

  onKeywordInput(e) {
    this.setData({
      keyword: e.detail.value
    });
  },

  onSearchConfirm() {
    this.onSearch();
  },

  onCategoryTap(e) {
    const category = e.currentTarget.dataset.category;
    wx.navigateTo({
      url: `/pages/service/list?category_id=${category.id}`
    });
  },

  onServiceTap(e) {
    const service = e.currentTarget.dataset.service;
    wx.navigateTo({
      url: `/pages/service/detail?id=${service.id}`
    });
  },

  onTechnicianTap(e) {
    const technician = e.currentTarget.dataset.technician;
    wx.navigateTo({
      url: `/pages/service/technician?id=${technician.id}`
    });
  },

  onNoticeTap(e) {
    const notice = e.currentTarget.dataset.notice;
    if (notice && notice.link) {
      wx.navigateTo({
        url: notice.link
      });
    }
  },

  onPopupClose() {
    this.setData({ showPopup: false });
  },

  onPopupTap() {
    const popup = this.data.popup;
    if (popup && popup.link) {
      this.setData({ showPopup: false });
      wx.navigateTo({
        url: popup.link
      });
    }
  },

  onGrabOrder() {
    wx.navigateTo({
      url: '/pages/order/grab'
    });
  },

  onQuickOrder() {
    wx.showActionSheet({
      itemList: ['选择服务下单', '选择技师下单', '选择门店下单'],
      success: (res) => {
        if (res.tapIndex === 0) {
          wx.navigateTo({ url: '/pages/service/list' });
        } else if (res.tapIndex === 1) {
          wx.navigateTo({ url: '/pages/service/technician' });
        } else {
          wx.navigateTo({ url: '/pages/store/list' });
        }
      }
    });
  },

  getDistanceText(distance) {
    return util.getDistanceText(distance);
  }
});
