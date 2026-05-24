const app = getApp();

Page({
  data: {
    version: '1.0.0',
    info: {
      name: '同城上门服务',
      desc: '同城上门服务平台，为您提供专业、便捷、可靠的上门服务体验。',
      features: [
        '专业认证：所有服务人员均经过严格审核和专业培训',
        '透明定价：明码标价，无隐形消费',
        '上门服务：足不出户，享受专业服务',
        '售后保障：服务不满意可申请退款'
      ],
      contact: {
        phone: '400-123-4567',
        email: 'service@tongcheng.com',
        address: '北京市朝阳区xxx大厦'
      }
    }
  },

  onLoad() {
    const accountInfo = wx.getAccountInfoSync();
    if (accountInfo && accountInfo.miniProgram) {
      this.setData({
        version: accountInfo.miniProgram.version || '1.0.0'
      });
    }
  },

  onCallPhone() {
    wx.makePhoneCall({
      phoneNumber: this.data.info.contact.phone,
      fail: () => {
        app.showToast('拨号失败');
      }
    });
  },

  onCheckUpdate() {
    const updateManager = wx.getUpdateManager();
    
    updateManager.onCheckForUpdate((res) => {
      if (res.hasUpdate) {
        wx.showLoading({ title: '检测到新版本...' });
      } else {
        app.showToast('已是最新版本');
      }
    });

    updateManager.onUpdateReady(() => {
      wx.hideLoading();
      wx.showModal({
        title: '更新提示',
        content: '新版本已准备好，是否重启应用？',
        success: (res) => {
          if (res.confirm) {
            updateManager.applyUpdate();
          }
        }
      });
    });

    updateManager.onUpdateFailed(() => {
      wx.hideLoading();
      app.showToast('更新失败');
    });
  }
});
