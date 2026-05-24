const app = getApp();

Page({
  data: {
    id: 0,
    name: '',
    mobile: '',
    province: '',
    city: '',
    district: '',
    detail: '',
    tag: '',
    isDefault: 0,
    region: [],
    tags: ['家', '公司', '学校', '其他'],
    currentTagIndex: -1,
    submitting: false
  },

  onLoad(options) {
    this.setData({
      id: parseInt(options.id) || 0
    });

    if (this.data.id > 0) {
      this.loadAddress();
    }
  },

  async loadAddress() {
    try {
      const res = await app.request({
        url: '/?c=user&a=addressDetail',
        method: 'GET',
        data: {
          id: this.data.id
        }
      });

      const addr = res.data;
      const currentTagIndex = this.data.tags.indexOf(addr.tag);

      this.setData({
        name: addr.name,
        mobile: addr.mobile,
        province: addr.province,
        city: addr.city,
        district: addr.district,
        detail: addr.detail,
        tag: addr.tag,
        isDefault: addr.is_default,
        region: [addr.province, addr.city, addr.district],
        currentTagIndex: currentTagIndex >= 0 ? currentTagIndex : -1
      });
    } catch (e) {
      console.error(e);
    }
  },

  onNameInput(e) {
    this.setData({ name: e.detail.value });
  },

  onMobileInput(e) {
    this.setData({ mobile: e.detail.value });
  },

  onDetailInput(e) {
    this.setData({ detail: e.detail.value });
  },

  onRegionChange(e) {
    const region = e.detail.value;
    this.setData({
      region,
      province: region[0],
      city: region[1],
      district: region[2]
    });
  },

  onTagTap(e) {
    const index = e.currentTarget.dataset.index;
    this.setData({
      currentTagIndex: this.data.currentTagIndex === index ? -1 : index,
      tag: this.data.currentTagIndex === index ? '' : this.data.tags[index]
    });
  },

  onDefaultChange(e) {
    this.setData({ isDefault: e.detail.value ? 1 : 0 });
  },

  async onSave() {
    if (this.data.submitting) return;

    if (!this.data.name.trim()) {
      app.showToast('请输入收货人姓名');
      return;
    }

    if (!this.data.mobile.trim()) {
      app.showToast('请输入手机号码');
      return;
    }

    if (!/^1[3-9]\d{9}$/.test(this.data.mobile.trim())) {
      app.showToast('请输入正确的手机号码');
      return;
    }

    if (!this.data.province || !this.data.city || !this.data.district) {
      app.showToast('请选择所在地区');
      return;
    }

    if (!this.data.detail.trim()) {
      app.showToast('请输入详细地址');
      return;
    }

    this.setData({ submitting: true });
    app.showLoading('保存中...');

    try {
      const res = await app.request({
        url: '/?c=user&a=addressSave',
        method: 'POST',
        data: {
          id: this.data.id,
          name: this.data.name.trim(),
          mobile: this.data.mobile.trim(),
          province: this.data.province,
          city: this.data.city,
          district: this.data.district,
          detail: this.data.detail.trim(),
          tag: this.data.tag,
          is_default: this.data.isDefault
        }
      });

      app.hideLoading();
      app.showToast('保存成功');

      const pages = getCurrentPages();
      const prevPage = pages[pages.length - 2];
      if (prevPage && prevPage.addressSaved) {
        prevPage.addressSaved(res.data);
      }

      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    } catch (e) {
      console.error(e);
      this.setData({ submitting: false });
      app.hideLoading();
    }
  },

  async onDelete() {
    if (this.data.id <= 0) {
      wx.navigateBack();
      return;
    }

    wx.showModal({
      title: '删除地址',
      content: '确定要删除该地址吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            await app.request({
              url: '/?c=user&a=addressDelete',
              method: 'POST',
              data: {
                id: this.data.id
              }
            });
            app.showToast('删除成功');
            setTimeout(() => {
              wx.navigateBack();
            }, 1500);
          } catch (e) {
            console.error(e);
          }
        }
      }
    });
  }
});
