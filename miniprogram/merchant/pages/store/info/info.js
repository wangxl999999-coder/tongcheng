const app = getApp();

Page({
  data: {
    storeInfo: null,
    form: {
      name: '',
      logo: '',
      phone: '',
      address: '',
      business_hours: '',
      description: '',
      status: 1
    },
    loading: true,
    submitting: false
  },

  onLoad() {
    this.loadStoreInfo();
  },

  async loadStoreInfo() {
    try {
      const res = await app.request({
        url: '/?c=store&a=detail',
        method: 'GET'
      });

      const store = res.data;
      this.setData({
        storeInfo: store,
        form: {
          name: store.name || '',
          logo: store.logo || '',
          phone: store.phone || '',
          address: store.address || '',
          business_hours: store.business_hours || '09:00-21:00',
          description: store.description || '',
          status: store.status !== undefined ? store.status : 1
        },
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onChooseLogo() {
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['album', 'camera'],
      success: async (res) => {
        const tempFilePath = res.tempFiles[0].tempFilePath;
        
        try {
          wx.showLoading({ title: '上传中...' });
          
          const uploadRes = await this.uploadImage(tempFilePath);
          
          wx.hideLoading();
          this.setData({
            'form.logo': uploadRes.url
          });
        } catch (e) {
          wx.hideLoading();
          console.error(e);
          app.showToast('上传失败');
        }
      }
    });
  },

  uploadImage(filePath) {
    return new Promise((resolve, reject) => {
      wx.uploadFile({
        url: app.globalData.baseUrl + '/?c=upload&a=image',
        filePath: filePath,
        name: 'file',
        header: {
          'Authorization': 'Bearer ' + app.globalData.token
        },
        success: (res) => {
          try {
            const data = JSON.parse(res.data);
            if (data.code === 0) {
              resolve(data.data);
            } else {
              reject(data);
            }
          } catch (e) {
            reject(e);
          }
        },
        fail: (err) => {
          reject(err);
        }
      });
    });
  },

  onInput(e) {
    const field = e.currentTarget.dataset.field;
    this.setData({
      [`form.${field}`]: e.detail.value
    });
  },

  onStatusChange(e) {
    const status = e.detail.value ? 1 : 0;
    this.setData({
      'form.status': status
    });
  },

  async onChooseAddress() {
    try {
      const res = await wx.chooseLocation();
      this.setData({
        'form.address': res.address + ' ' + res.name
      });
    } catch (e) {
      if (e.errMsg !== 'chooseLocation:fail cancel') {
        app.showToast('获取位置失败');
      }
    }
  },

  async onSubmit() {
    if (!this.data.form.name) {
      app.showToast('请输入门店名称');
      return;
    }

    if (!this.data.form.logo) {
      app.showToast('请上传门店Logo');
      return;
    }

    if (!this.data.form.phone) {
      app.showToast('请输入联系电话');
      return;
    }

    if (!this.data.form.address) {
      app.showToast('请输入门店地址');
      return;
    }

    try {
      this.setData({ submitting: true });
      wx.showLoading({ title: '保存中...' });
      
      const res = await app.request({
        url: '/?c=store&a=update',
        method: 'POST',
        data: this.data.form
      });

      wx.hideLoading();
      this.setData({ submitting: false });
      
      app.globalData.storeInfo = res.data;
      wx.setStorageSync('store_info', res.data);
      
      app.showToast('保存成功', 'success');
      
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    } catch (e) {
      wx.hideLoading();
      this.setData({ submitting: false });
      console.error(e);
    }
  }
});
