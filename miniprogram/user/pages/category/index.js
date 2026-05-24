const app = getApp();

Page({
  data: {
    categories: [],
    currentCategoryId: 0,
    currentCategory: null,
    services: [],
    loading: true
  },

  onLoad() {
    this.loadCategories();
  },

  onPullDownRefresh() {
    this.loadCategories().then(() => {
      wx.stopPullDownRefresh();
    });
  },

  async loadCategories() {
    try {
      const res = await app.request({
        url: '/?c=category&a=index',
        method: 'GET'
      });

      const categories = res.data || [];
      const currentCategoryId = categories.length > 0 ? categories[0].id : 0;
      const currentCategory = categories.length > 0 ? categories[0] : null;

      this.setData({
        categories,
        currentCategoryId,
        currentCategory,
        loading: false
      });

      if (currentCategoryId > 0) {
        this.loadServices(currentCategoryId);
      }
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  async loadServices(categoryId) {
    try {
      const res = await app.request({
        url: '/?c=service&a=list',
        method: 'GET',
        data: {
          category_id: categoryId,
          page_size: 50
        }
      });

      this.setData({
        services: res.data.list || []
      });
    } catch (e) {
      console.error(e);
    }
  },

  onCategoryTap(e) {
    const category = e.currentTarget.dataset.category;
    this.setData({
      currentCategoryId: category.id,
      currentCategory: category
    });
    this.loadServices(category.id);
  },

  onSubCategoryTap(e) {
    const subCategory = e.currentTarget.dataset.subcategory;
    wx.navigateTo({
      url: `/pages/service/list?category_id=${subCategory.id}`
    });
  },

  onServiceTap(e) {
    const service = e.currentTarget.dataset.service;
    wx.navigateTo({
      url: `/pages/service/detail?id=${service.id}`
    });
  },

  onViewAll() {
    wx.navigateTo({
      url: `/pages/service/list?category_id=${this.data.currentCategoryId}`
    });
  }
});
