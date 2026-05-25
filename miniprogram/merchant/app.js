App({
  globalData: {
    merchantInfo: null,
    storeInfo: null,
    token: '',
    baseUrl: 'http://localhost/tongcheng/api'
  },

  onLaunch() {
    const token = wx.getStorageSync('merchant_token');
    const merchantInfo = wx.getStorageSync('merchant_info');
    const storeInfo = wx.getStorageSync('store_info');
    
    if (token) {
      this.globalData.token = token;
    }
    if (merchantInfo) {
      this.globalData.merchantInfo = merchantInfo;
    }
    if (storeInfo) {
      this.globalData.storeInfo = storeInfo;
    }

    this.checkLogin();
  },

  async checkLogin() {
    if (!this.globalData.token) {
      wx.reLaunch({
        url: '/pages/login/login'
      });
      return false;
    }
    return true;
  },

  async login(option) {
    return new Promise((resolve, reject) => {
      wx.login({
        success: async (res) => {
          if (res.code) {
            wx.request({
              url: this.globalData.baseUrl + '/?c=merchant&a=login',
              method: 'POST',
              data: {
                code: res.code
              },
              success: (response) => {
                  if (response.data.code === 0) {
                    const data = response.data.data;
                    this.globalData.token = data.token;
                    this.globalData.merchantInfo = data.merchant;
                    this.globalData.storeInfo = data.store;
                    wx.setStorageSync('merchant_token', data.token);
                    wx.setStorageSync('merchant_info', data.merchant);
                    wx.setStorageSync('store_info', data.store);
                    resolve(data);
                  } else {
                    reject(response.data);
                  }
                },
                fail: (err) => {
                  reject(err);
                }
              });
          } else {
            reject(res);
          }
        },
        fail: (err) => {
          reject(err);
        }
      });
    });
  },

  request(option) {
    return new Promise((resolve, reject) => {
      const header = {
        'content-type': 'application/json'
      };

      if (this.globalData.token) {
        header['Authorization'] = 'Bearer ' + this.globalData.token;
      }

      wx.request({
        url: this.globalData.baseUrl + option.url,
        method: option.method || 'GET',
        data: option.data || {},
        header: header,
        success: (res) => {
          if (res.data.code === 0) {
            resolve(res.data);
          } else if (res.data.code === 401) {
            wx.removeStorageSync('merchant_token');
            wx.removeStorageSync('merchant_info');
            wx.removeStorageSync('store_info');
            this.globalData.token = '';
            this.globalData.merchantInfo = null;
            this.globalData.storeInfo = null;
            wx.reLaunch({
              url: '/pages/login/login'
            });
            reject(res.data);
          } else {
            this.showToast(res.data.msg || '请求失败');
            reject(res.data);
          }
        },
        fail: (err) => {
          this.showToast('网络连接失败');
          reject(err);
        }
      });
    });
  },

  showToast(title, icon = 'none') {
    wx.showToast({
      title: title,
      icon: icon,
      duration: 2000
    });
  },

  formatTime(date) {
    if (!date) return '';
    const d = new Date(date);
    const year = d.getFullYear();
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');
    const hour = d.getHours().toString().padStart(2, '0');
    const minute = d.getMinutes().toString().padStart(2, '0');
    return `${year}-${month}-${day} ${hour}:${minute}`;
  },

  formatDate(date) {
    if (!date) return '';
    const d = new Date(date);
    const year = d.getFullYear();
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  }
});
