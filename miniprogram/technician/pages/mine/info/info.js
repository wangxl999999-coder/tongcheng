const app = getApp();

Page({
  data: {
    form: {
      name: '',
      phone: '',
      avatar: '',
      id_card: '',
      skills: [],
      intro: '',
      experience: ''
    },
    skillOptions: [],
    loading: true
  },

  onLoad() {
    this.loadData();
  },

  async loadData() {
    try {
      const res = await app.request({
        url: '/?c=technician&a=profile',
        method: 'GET'
      });

      this.setData({
        form: res.data.profile || this.data.form,
        skillOptions: res.data.skills || [],
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onChooseAvatar() {
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['album', 'camera'],
      success: async (res) => {
        const tempFile = res.tempFiles[0].tempFilePath;
        
        try {
          wx.showLoading({ title: '上传中...' });
          
          const uploadRes = await new Promise((resolve, reject) => {
            wx.uploadFile({
              url: app.globalData.baseUrl + '/?c=upload&a=image',
              filePath: tempFile,
              name: 'file',
              header: {
                'Authorization': 'Bearer ' + app.globalData.token
              },
              success: (res) => {
                const data = JSON.parse(res.data);
                if (data.code === 0) {
                  resolve(data);
                } else {
                  reject(data);
                }
              },
              fail: reject
            });
          });

          wx.hideLoading();
          
          this.setData({
            'form.avatar': uploadRes.data.url
          });
          
          app.showToast('上传成功', 'success');
        } catch (e) {
          wx.hideLoading();
          console.error(e);
          app.showToast('上传失败');
        }
      }
    });
  },

  onInput(e) {
    const field = e.currentTarget.dataset.field;
    this.setData({
      [`form.${field}`]: e.detail.value
    });
  },

  onSkillChange(e) {
    this.setData({
      'form.skills': e.detail.value
    });
  },

  async onSubmit() {
    if (!this.data.form.name) {
      app.showToast('请输入姓名');
      return;
    }

    try {
      wx.showLoading({ title: '保存中...' });
      
      await app.request({
        url: '/?c=technician&a=updateProfile',
        method: 'POST',
        data: this.data.form
      });

      wx.hideLoading();
      app.showToast('保存成功', 'success');
      
      app.globalData.technicianInfo = {
        ...app.globalData.technicianInfo,
        ...this.data.form
      };
      wx.setStorageSync('technician_info', app.globalData.technicianInfo);
      
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    } catch (e) {
      wx.hideLoading();
      console.error(e);
    }
  }
});
