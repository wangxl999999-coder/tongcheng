const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    serviceId: 0,
    service: null,
    currentSpec: null,
    currentSpecIndex: 0,
    showSpecPicker: false,
    isCollected: false,
    reviews: [],
    loading: true
  },

  onLoad(options) {
    this.setData({
      serviceId: parseInt(options.id) || 0
    });
    this.loadDetail();
    this.loadReviews();
  },

  async loadDetail() {
    this.setData({ loading: true });
    try {
      const res = await app.request({
        url: '/?c=service&a=detail',
        method: 'GET',
        data: {
          id: this.data.serviceId
        }
      });

      const service = res.data;
      const currentSpec = service.specs && service.specs.length > 0 ? service.specs[0] : null;
      
      this.setData({
        service,
        currentSpec,
        isCollected: service.is_collected || false,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  async loadReviews() {
    try {
      const res = await app.request({
        url: '/?c=service&a=reviews',
        method: 'GET',
        data: {
          service_id: this.data.serviceId,
          page: 1,
          page_size: 3
        }
      });

      this.setData({
        reviews: res.data.list || []
      });
    } catch (e) {
      console.error(e);
    }
  },

  onPreviewImage(e) {
    const current = e.currentTarget.dataset.src;
    const urls = this.data.service.images || [];
    wx.previewImage({ current, urls });
  },

  onSpecTap(e) {
    const index = e.currentTarget.dataset.index;
    const spec = this.data.service.specs[index];
    this.setData({
      currentSpec: spec,
      currentSpecIndex: index
    });
  },

  showSpecPicker() {
    this.setData({ showSpecPicker: true });
  },

  hideSpecPicker() {
    this.setData({ showSpecPicker: false });
  },

  async onCollect() {
    if (!app.checkLogin()) return;

    try {
      await app.request({
        url: '/?c=service&a=collect',
        method: 'POST',
        data: {
          id: this.data.serviceId,
          type: this.data.isCollected ? 2 : 1
        }
      });

      this.setData({
        isCollected: !this.data.isCollected
      });

      app.showToast(this.data.isCollected ? '已收藏' : '已取消收藏');
    } catch (e) {
      console.error(e);
    }
  },

  onTechnicianTap(e) {
    const technician = e.currentTarget.dataset.technician;
    wx.navigateTo({
      url: `/pages/service/technician?id=${technician.id}`
    });
  },

  onChooseTechnician() {
    wx.navigateTo({
      url: `/pages/service/technician-list?service_id=${this.data.serviceId}`
    });
  },

  onBuyNow() {
    if (!app.checkLogin()) return;
    
    this.setData({ showSpecPicker: true });
  },

  confirmSpec() {
    this.setData({ showSpecPicker: false });
    
    const spec = this.data.currentSpec;
    const service = this.data.service;
    
    wx.navigateTo({
      url: `/pages/order/create?service_id=${service.id}&spec_id=${spec.id}`
    });
  },

  onAllReviews() {
    wx.navigateTo({
      url: `/pages/service/reviews?id=${this.data.serviceId}`
    });
  },

  formatTime(timestamp) {
    return util.formatDateTime(timestamp);
  }
});
