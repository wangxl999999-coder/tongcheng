const app = getApp();

Page({
  data: {
    userInfo: null,
    nickname: '',
    avatar: '',
    gender: 0,
    birthday: '',
    loading: true
  },

  onLoad() {
    this.loadUserInfo();
  },

  async loadUserInfo() {
    this.setData({ loading: true });
    try {
      const res = await app.request({
        url: '/?c=user&a=info',
        method: 'GET'
      });

      const userInfo = res.data;
      this.setData({
        userInfo,
        nickname: userInfo.nickname || '',
        avatar: userInfo.avatar || '',
        gender: userInfo.gender || 0,
        birthday: userInfo.birthday || '',
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
      sizeType: ['compressed'],
      success: async (res) => {
        const tempFilePath = res.tempFiles[0].tempFilePath;
        
        try {
          const uploadRes = await app.uploadFile({
            url: '/?c=upload&a=image',
            filePath: tempFilePath,
            name: 'file'
          });

          this.setData({
            avatar: uploadRes.data.url
          });
          app.showToast('上传成功');
        } catch (e) {
          console.error(e);
          app.showToast('上传失败');
        }
      }
    });
  },

  onNicknameInput(e) {
    this.setData({ nickname: e.detail.value });
  },

  onGenderTap(e) {
    const gender = e.currentTarget.dataset.gender;
    this.setData({ gender: parseInt(gender) });
  },

  onBirthdayChange(e) {
    this.setData({ birthday: e.detail.value });
  },

  async onSave() {
    if (!this.data.nickname.trim()) {
      app.showToast('请输入昵称');
      return;
    }

    app.showLoading('保存中...');
    try {
      await app.request({
        url: '/?c=user&a=updateInfo',
        method: 'POST',
        data: {
          nickname: this.data.nickname.trim(),
          avatar: this.data.avatar,
          gender: this.data.gender,
          birthday: this.data.birthday
        }
      });

      app.globalData.userInfo.nickname = this.data.nickname;
      app.globalData.userInfo.avatar = this.data.avatar;
      wx.setStorageSync('userInfo', app.globalData.userInfo);

      app.hideLoading();
      app.showToast('保存成功');
      
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    } catch (e) {
      console.error(e);
      app.hideLoading();
    }
  },

  onBindMobile() {
    wx.navigateTo({
      url: '/pages/mine/bind-mobile'
    });
  }
});
