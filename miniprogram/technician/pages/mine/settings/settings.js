const app = getApp();

Page({
  data: {
    settings: {
      auto_accept: 0,
      service_range: 5,
      work_days: [1, 2, 3, 4, 5, 6, 7],
      work_start: '09:00',
      work_end: '21:00',
      notice_sound: 1,
      notice_vibrate: 1
    },
    weekDays: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
    loading: true
  },

  onLoad() {
    this.loadSettings();
  },

  async loadSettings() {
    try {
      const res = await app.request({
        url: '/?c=technician&a=getSettings',
        method: 'GET'
      });

      this.setData({
        settings: res.data || this.data.settings,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onAutoAcceptChange(e) {
    this.setData({
      'settings.auto_accept': e.detail.value ? 1 : 0
    });
    this.saveSettings();
  },

  onRangeChange(e) {
    this.setData({
      'settings.service_range': e.detail.value
    });
  },

  onDayChange(e) {
    const day = parseInt(e.currentTarget.dataset.day);
    const workDays = [...this.data.settings.work_days];
    const index = workDays.indexOf(day);
    
    if (index > -1) {
      workDays.splice(index, 1);
    } else {
      workDays.push(day);
      workDays.sort((a, b) => a - b);
    }
    
    this.setData({
      'settings.work_days': workDays
    });
  },

  onStartTimeChange(e) {
    this.setData({
      'settings.work_start': e.detail.value
    });
  },

  onEndTimeChange(e) {
    this.setData({
      'settings.work_end': e.detail.value
    });
  },

  onSoundChange(e) {
    this.setData({
      'settings.notice_sound': e.detail.value ? 1 : 0
    });
  },

  onVibrateChange(e) {
    this.setData({
      'settings.notice_vibrate': e.detail.value ? 1 : 0
    });
  },

  async saveSettings() {
    try {
      await app.request({
        url: '/?c=technician&a=saveSettings',
        method: 'POST',
        data: this.data.settings
      });
    } catch (e) {
      console.error(e);
    }
  },

  async onSubmit() {
    try {
      wx.showLoading({ title: '保存中...' });
      
      await app.request({
        url: '/?c=technician&a=saveSettings',
        method: 'POST',
        data: this.data.settings
      });

      wx.hideLoading();
      app.showToast('保存成功', 'success');
      
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    } catch (e) {
      wx.hideLoading();
      console.error(e);
    }
  }
});
