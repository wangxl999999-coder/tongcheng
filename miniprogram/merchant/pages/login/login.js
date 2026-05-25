const app = getApp();

Page({
  data: {
    phone: '',
    password: '',
    code: '',
    sending: false,
    countdown: 0,
    loginType: 1,
    agreementChecked: false
  },

  onLoad() {
    const token = wx.getStorageSync('merchant_token');
    if (token) {
      wx.switchTab({
        url: '/pages/index/index'
      });
    }
  },

  onPhoneInput(e) {
    this.setData({
      phone: e.detail.value
    });
  },

  onPasswordInput(e) {
    this.setData({
      password: e.detail.value
    });
  },

  onCodeInput(e) {
    this.setData({
      code: e.detail.value
    });
  },

  onTabChange(e) {
    const type = parseInt(e.currentTarget.dataset.type);
    this.setData({
      loginType: type
    });
  },

  onSendCode() {
    if (!this.data.phone) {
      app.showToast('请输入手机号');
      return;
    }

    if (!/^1[3-9]\d{9}$/.test(this.data.phone)) {
      app.showToast('手机号格式不正确');
      return;
    }

    this.setData({
      sending: true,
      countdown: 60
    });

    const timer = setInterval(() => {
      const countdown = this.data.countdown - 1;
      this.setData({ countdown });
      if (countdown <= 0) {
        clearInterval(timer);
        this.setData({
          sending: false,
          countdown: 0
        });
      }
    }, 1000);

    app.request({
      url: '/?c=merchant&a=sendCode',
      method: 'POST',
      data: {
        phone: this.data.phone
      }
    }).then(() => {
      app.showToast('验证码已发送');
    }).catch(() => {
      clearInterval(timer);
      this.setData({
        sending: false,
        countdown: 0
      });
    });
  },

  onAgreementChange(e) {
    this.setData({
      agreementChecked: e.detail.value
    });
  },

  async onLogin() {
    if (!this.data.agreementChecked) {
      app.showToast('请先同意用户协议');
      return;
    }

    if (!this.data.phone) {
      app.showToast('请输入手机号');
      return;
    }

    if (!/^1[3-9]\d{9}$/.test(this.data.phone)) {
      app.showToast('手机号格式不正确');
      return;
    }

    if (this.data.loginType === 1) {
      if (!this.data.password) {
        app.showToast('请输入密码');
        return;
      }
    } else {
      if (!this.data.code) {
        app.showToast('请输入验证码');
        return;
      }
    }

    try {
      wx.showLoading({ title: '登录中...' });
      
      const res = await app.request({
        url: '/?c=merchant&a=login',
        method: 'POST',
        data: {
          phone: this.data.phone,
          password: this.data.password,
          code: this.data.code,
          type: this.data.loginType
        }
      });

      wx.hideLoading();
      
      app.globalData.token = res.data.token;
      app.globalData.merchantInfo = res.data.merchant;
      app.globalData.storeInfo = res.data.store;
      wx.setStorageSync('merchant_token', res.data.token);
      wx.setStorageSync('merchant_info', res.data.merchant);
      wx.setStorageSync('store_info', res.data.store);
      
      app.showToast('登录成功', 'success');
      
      setTimeout(() => {
        wx.switchTab({
          url: '/pages/index/index'
        });
      }, 1500);
    } catch (e) {
      wx.hideLoading();
      console.error(e);
    }
  },

  async onWxLogin() {
    if (!this.data.agreementChecked) {
      app.showToast('请先同意用户协议');
      return;
    }

    try {
      wx.showLoading({ title: '登录中...' });
      
      const res = await app.login();
      
      wx.hideLoading();
      
      app.showToast('登录成功', 'success');
      
      setTimeout(() => {
        wx.switchTab({
          url: '/pages/index/index'
        });
      }, 1500);
    } catch (e) {
      wx.hideLoading();
      console.error(e);
      app.showToast(e.msg || '登录失败');
    }
  },

  onShowAgreement() {
    wx.navigateTo({
      url: '/pages/mine/agreement/agreement'
    });
  },

  onShowPrivacy() {
    wx.navigateTo({
      url: '/pages/mine/privacy/privacy'
    });
  }
});
