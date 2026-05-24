const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    technicianId: 0,
    technician: null,
    services: [],
    schedule: [],
    selectedDate: null,
    selectedTime: null,
    reviews: [],
    days: [],
    times: [],
    currentDayIndex: 0,
    loading: true
  },

  onLoad(options) {
    this.setData({
      technicianId: parseInt(options.id) || 0
    });
    this.initDateTime();
    this.loadDetail();
    this.loadServices();
    this.loadSchedule();
    this.loadReviews();
  },

  initDateTime() {
    const days = util.getDaysArray(7);
    const times = util.getTimesArray(8, 22);
    this.setData({
      days,
      times,
      currentDayIndex: 0
    });
  },

  async loadDetail() {
    try {
      const res = await app.request({
        url: '/?c=technician&a=detail',
        method: 'GET',
        data: {
          id: this.data.technicianId
        }
      });

      this.setData({
        technician: res.data,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  async loadServices() {
    try {
      const res = await app.request({
        url: '/?c=technician&a=services',
        method: 'GET',
        data: {
          id: this.data.technicianId
        }
      });

      this.setData({
        services: res.data.list || []
      });
    } catch (e) {
      console.error(e);
    }
  },

  async loadSchedule() {
    try {
      const day = this.data.days[this.data.currentDayIndex];
      const res = await app.request({
        url: '/?c=technician&a=schedule',
        method: 'GET',
        data: {
          id: this.data.technicianId,
          date: day.date
        }
      });

      const schedule = res.data || [];
      const times = this.data.times.map(time => {
        const booked = schedule.some(s => s.time === time.time);
        return { ...time, disabled: booked };
      });

      this.setData({
        schedule,
        times
      });
    } catch (e) {
      console.error(e);
    }
  },

  async loadReviews() {
    try {
      const res = await app.request({
        url: '/?c=technician&a=reviews',
        method: 'GET',
        data: {
          id: this.data.technicianId,
          page: 1,
          page_size: 5
        }
      });

      this.setData({
        reviews: res.data.list || []
      });
    } catch (e) {
      console.error(e);
    }
  },

  onDayTap(e) {
    const index = e.currentTarget.dataset.index;
    this.setData({
      currentDayIndex: index,
      selectedTime: null
    });
    this.loadSchedule();
  },

  onTimeTap(e) {
    const index = e.currentTarget.dataset.index;
    const time = this.data.times[index];
    if (time.disabled) return;

    this.setData({
      selectedTime: time.time
    });
  },

  onServiceTap(e) {
    const service = e.currentTarget.dataset.service;
    wx.navigateTo({
      url: `/pages/service/detail?id=${service.id}`
    });
  },

  onPreviewAvatar() {
    if (this.data.technician && this.data.technician.avatar) {
      wx.previewImage({
        urls: [this.data.technician.avatar]
      });
    }
  },

  onCall() {
    if (this.data.technician && this.data.technician.mobile) {
      wx.makePhoneCall({
        phoneNumber: this.data.technician.mobile,
        fail: () => {
          app.showToast('拨号失败');
        }
      });
    }
  },

  onBook() {
    if (!app.checkLogin()) return;

    if (this.data.services.length === 0) {
      app.showToast('该技师暂无可预约服务');
      return;
    }

    if (!this.data.selectedTime) {
      app.showToast('请选择服务时间');
      return;
    }

    const service = this.data.services[0];
    const day = this.data.days[this.data.currentDayIndex];
    const serviceTime = day.timestamp + this.data.selectedTime.split(':')[0] * 3600;

    wx.navigateTo({
      url: `/pages/order/create?service_id=${service.id}&technician_id=${this.data.technicianId}&time=${serviceTime}`
    });
  },

  onQuickBook(e) {
    if (!app.checkLogin()) return;

    const service = e.currentTarget.dataset.service;
    const day = this.data.days[this.data.currentDayIndex];
    const serviceTime = day.timestamp + (this.data.selectedTime ? this.data.selectedTime.split(':')[0] * 3600 : 0);

    wx.navigateTo({
      url: `/pages/order/create?service_id=${service.id}&technician_id=${this.data.technicianId}&time=${serviceTime}`
    });
  },

  formatTime(timestamp) {
    return util.formatDateTime(timestamp);
  },

  formatDistance(distance) {
    return util.formatDistance(distance);
  }
});
