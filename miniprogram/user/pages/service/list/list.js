const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    keyword: '',
    categoryId: 0,
    cityId: 0,
    sort: 'default',
    sortOptions: [
      { value: 'default', label: '综合' },
      { value: 'sales', label: '销量' },
      { value: 'price_asc', label: '价格↑' },
      { value: 'price_desc', label: '价格↓' },
      { value: 'rating', label: '评分' }
    ],
    services: [],
    page: 1,
    pageSize: 20,
    total: 0,
    hasMore: true,
    loading: false,
    longitude: 0,
    latitude: 0
  },

  onLoad(options) {
    this.setData({
      keyword: options.keyword || '',
      categoryId: parseInt(options.category_id) || 0,
      cityId: app.globalData.cityId,
      longitude: app.globalData.longitude,
      latitude: app.globalData.latitude
    });

    if (this.data.keyword) {
      wx.setNavigationBarTitle({
        title: '搜索：' + this.data.keyword
      });
    }

    this.loadServices(true);
  },

  onPullDownRefresh() {
    this.loadServices(true).then(() => {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadServices(false);
    }
  },

  async loadServices(refresh = false) {
    if (this.data.loading) return;

    this.setData({ loading: true });
    app.showLoading();

    try {
      const page = refresh ? 1 : this.data.page;
      const res = await app.request({
        url: '/?c=service&a=list',
        method: 'GET',
        data: {
          keyword: this.data.keyword,
          category_id: this.data.categoryId,
          city_id: this.data.cityId,
          sort: this.data.sort,
          page: page,
          page_size: this.data.pageSize,
          longitude: this.data.longitude,
          latitude: this.data.latitude
        }
      });

      const newServices = res.data.list || [];
      const services = refresh ? newServices : [...this.data.services, ...newServices];

      this.setData({
        services,
        total: res.data.total || 0,
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

  onSortTap(e) {
    const sort = e.currentTarget.dataset.sort;
    if (sort === this.data.sort) return;

    this.setData({ sort });
    this.loadServices(true);
  },

  onServiceTap(e) {
    const service = e.currentTarget.dataset.service;
    wx.navigateTo({
      url: `/pages/service/detail?id=${service.id}`
    });
  },

  onKeywordInput(e) {
    this.setData({
      keyword: e.detail.value
    });
  },

  onSearch() {
    this.loadServices(true);
  },

  onSearchConfirm() {
    this.loadServices(true);
  },

  getDistanceText(distance) {
    return util.getDistanceText(distance);
  }
});
