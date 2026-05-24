App({
  globalData: {
    baseUrl: 'https://your-domain.com/api',
    userInfo: null,
    token: '',
    cityId: 0,
    cityName: '选择城市',
    longitude: 0,
    latitude: 0
  },

  onLaunch() {
    const token = wx.getStorageSync('token');
    const userInfo = wx.getStorageSync('userInfo');
    const cityId = wx.getStorageSync('cityId');
    const cityName = wx.getStorageSync('cityName');

    if (token) {
      this.globalData.token = token;
    }
    if (userInfo) {
      this.globalData.userInfo = userInfo;
    }
    if (cityId) {
      this.globalData.cityId = cityId;
      this.globalData.cityName = cityName;
    }

    this.getLocation();
  },

  getLocation() {
    wx.getLocation({
      type: 'gcj02',
      success: (res) => {
        this.globalData.longitude = res.longitude;
        this.globalData.latitude = res.latitude;
      },
      fail: () => {
        console.log('获取位置失败');
      }
    });
  },

  request(options) {
    const { url, method = 'GET', data = {}, header = {} } = options;
    
    return new Promise((resolve, reject) => {
      wx.request({
        url: this.globalData.baseUrl + url,
        method,
        data,
        header: {
          'content-type': 'application/json',
          'token': this.globalData.token,
          ...header
        },
        success: (res) => {
          if (res.data.code === 0) {
            resolve(res.data);
          } else if (res.data.code === 401) {
            this.globalData.token = '';
            this.globalData.userInfo = null;
            wx.removeStorageSync('token');
            wx.removeStorageSync('userInfo');
            wx.showToast({
              title: '请先登录',
              icon: 'none'
            });
            setTimeout(() => {
              wx.navigateTo({
                url: '/pages/login/index'
              });
            }, 1000);
            reject(res.data);
          } else {
            wx.showToast({
              title: res.data.msg || '请求失败',
              icon: 'none'
            });
            reject(res.data);
          }
        },
        fail: (err) => {
          wx.showToast({
            title: '网络错误',
            icon: 'none'
          });
          reject(err);
        }
      });
    });
  },

  login() {
    return new Promise((resolve, reject) => {
      wx.login({
        success: (res) => {
          if (res.code) {
            wx.getUserProfile({
              desc: '用于完善会员资料',
              success: (profileRes) => {
                this.request({
                  url: '/?c=login&a=wxlogin',
                  method: 'POST',
                  data: {
                    code: res.code,
                    nickname: profileRes.userInfo.nickName,
                    avatar: profileRes.userInfo.avatarUrl,
                    gender: profileRes.userInfo.gender,
                    longitude: this.globalData.longitude,
                    latitude: this.globalData.latitude
                  }
                }).then((result) => {
                  this.globalData.token = result.data.token;
                  this.globalData.userInfo = result.data.user;
                  wx.setStorageSync('token', result.data.token);
                  wx.setStorageSync('userInfo', result.data.user);
                  resolve(result.data);
                }).catch(reject);
              },
              fail: reject
            });
          } else {
            reject(res);
          }
        },
        fail: reject
      });
    });
  },

  checkLogin() {
    if (!this.globalData.token || !this.globalData.userInfo) {
      return false;
    }
    return true;
  },

  formatImage(url) {
    if (!url) return '';
    if (url.indexOf('http') === 0) return url;
    return url;
  },

  formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hour}:${minute}`;
  },

  formatDate(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  },

  formatMoney(amount) {
    return '¥' + parseFloat(amount).toFixed(2);
  },

  showToast(title, icon = 'none') {
    wx.showToast({
      title,
      icon,
      duration: 2000
    });
  },

  showLoading(title = '加载中...') {
    wx.showLoading({
      title,
      mask: true
    });
  },

  hideLoading() {
    wx.hideLoading();
  }
});
