const app = getApp();
const util = require('../../../utils/util.js');

Page({
  data: {
    serviceId: 0,
    specId: 0,
    technicianId: 0,
    storeId: 0,
    addressId: 0,
    couponId: 0,
    serviceType: 1,
    dispatchType: 0,

    service: null,
    currentSpec: null,
    technician: null,
    address: null,
    coupon: null,

    serviceTime: 0,
    dateList: [],
    timeList: [],
    selectedDate: null,
    selectedTime: null,

    remark: '',
    useBalance: 0,
    totalAmount: 0,
    discountAmount: 0,
    payAmount: 0,
    availableCoupons: [],
    showCouponPicker: false,

    days: [],
    times: [],
    currentDayIndex: 0,
    currentTimeIndex: -1,

    userInfo: null,
    contactName: '',
    contactMobile: '',

    loading: true,
    submitting: false
  },

  onLoad(options) {
    if (!app.checkLogin()) {
      app.login().then(() => {
        this.initData(options);
      }).catch(() => {
        wx.navigateBack();
      });
      return;
    }
    this.initData(options);
  },

  initData(options) {
    this.setData({
      serviceId: parseInt(options.service_id) || 0,
      specId: parseInt(options.spec_id) || 0,
      technicianId: parseInt(options.technician_id) || 0,
      userInfo: app.globalData.userInfo,
      contactName: app.globalData.userInfo ? app.globalData.userInfo.nickname : '',
      contactMobile: app.globalData.userInfo ? app.globalData.userInfo.mobile : ''
    });

    this.initDateTime();
    this.loadService();
    this.loadDefaultAddress();
    this.loadAvailableCoupons();
  },

  initDateTime() {
    const days = util.getDaysArray(7);
    const times = util.getTimesArray(8, 22);
    this.setData({
      days,
      times,
      currentDayIndex: 0,
      currentTimeIndex: -1
    });
  },

  async loadService() {
    try {
      const res = await app.request({
        url: '/?c=service&a=detail',
        method: 'GET',
        data: {
          id: this.data.serviceId
        }
      });

      const service = res.data;
      const currentSpec = this.data.specId > 0 
        ? service.specs.find(s => s.id === this.data.specId) 
        : (service.specs && service.specs.length > 0 ? service.specs[0] : null);

      const technician = this.data.technicianId > 0 
        ? service.technicians.find(t => t.id === this.data.technicianId)
        : null;

      const totalAmount = currentSpec ? currentSpec.price : service.price;

      this.setData({
        service,
        currentSpec,
        technician,
        totalAmount,
        payAmount: totalAmount,
        loading: false,
        dispatchType: service.dispatch_type
      });

      this.calculatePrice();
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  async loadDefaultAddress() {
    try {
      const res = await app.request({
        url: '/?c=user&a=addressDefault',
        method: 'GET'
      });

      if (res.data) {
        this.setData({
          address: res.data,
          addressId: res.data.id,
          contactName: res.data.name,
          contactMobile: res.data.mobile
        });
      }
    } catch (e) {
      console.error(e);
    }
  },

  async loadAvailableCoupons() {
    try {
      const res = await app.request({
        url: '/?c=user&a=couponAvailable',
        method: 'GET',
        data: {
          amount: this.data.totalAmount
        }
      });

      this.setData({
        availableCoupons: res.data || []
      });
    } catch (e) {
      console.error(e);
    }
  },

  onDayTap(e) {
    const index = e.currentTarget.dataset.index;
    this.setData({
      currentDayIndex: index
    });
  },

  onTimeTap(e) {
    const index = e.currentTarget.dataset.index;
    const time = this.data.times[index];
    if (time.disabled) return;

    this.setData({
      currentTimeIndex: index
    });
  },

  onSpecTap(e) {
    const spec = e.currentTarget.dataset.spec;
    this.setData({
      currentSpec: spec,
      specId: spec.id,
      totalAmount: spec.price
    });
    this.calculatePrice();
    this.loadAvailableCoupons();
  },

  onServiceTypeTap(e) {
    const type = e.currentTarget.dataset.type;
    this.setData({
      serviceType: type,
      addressId: 0,
      address: null,
      storeId: 0
    });
  },

  onChooseAddress() {
    wx.navigateTo({
      url: '/pages/mine/address?select=1',
      events: {
        selectAddress: (address) => {
          this.setData({
            address,
            addressId: address.id,
            contactName: address.name,
            contactMobile: address.mobile
          });
        }
      }
    });
  },

  onAddAddress() {
    wx.navigateTo({
      url: '/pages/mine/address-edit',
      events: {
        addressSaved: (address) => {
          this.setData({
            address,
            addressId: address.id,
            contactName: address.name,
            contactMobile: address.mobile
          });
        }
      }
    });
  },

  onChooseCoupon() {
    this.setData({ showCouponPicker: true });
  },

  hideCouponPicker() {
    this.setData({ showCouponPicker: false });
  },

  onSelectCoupon(e) {
    const coupon = e.currentTarget.dataset.coupon;
    this.setData({
      coupon,
      couponId: coupon.id,
      showCouponPicker: false
    });
    this.calculatePrice();
  },

  onBalanceChange(e) {
    const useBalance = e.detail.value ? Math.min(this.data.userInfo.balance, this.data.payAmount) : 0;
    this.setData({ useBalance });
    this.calculatePrice();
  },

  onRemarkInput(e) {
    this.setData({ remark: e.detail.value });
  },

  onContactNameInput(e) {
    this.setData({ contactName: e.detail.value });
  },

  onContactMobileInput(e) {
    this.setData({ contactMobile: e.detail.value });
  },

  calculatePrice() {
    const totalAmount = this.data.currentSpec ? this.data.currentSpec.price : this.data.service.price;
    let discountAmount = 0;

    if (this.data.coupon) {
      const coupon = this.data.coupon;
      if (coupon.amount > 0) {
        discountAmount += coupon.amount;
      } else {
        discountAmount += totalAmount * (100 - coupon.discount) / 100;
      }
    }

    const level = this.data.userInfo && this.data.userInfo.level;
    if (level && level.discount < 100) {
      discountAmount += totalAmount * (100 - level.discount) / 100;
    }

    let payAmount = Math.max(0, totalAmount - discountAmount);

    if (this.data.useBalance > 0) {
      payAmount = Math.max(0, payAmount - this.data.useBalance);
    }

    this.setData({
      totalAmount,
      discountAmount,
      payAmount
    });
  },

  async onSubmit() {
    if (this.data.submitting) return;

    if (this.data.currentTimeIndex < 0) {
      app.showToast('请选择服务时间');
      return;
    }

    if (this.data.serviceType === 1 && !this.data.addressId) {
      app.showToast('请选择服务地址');
      return;
    }

    if (this.data.serviceType === 2 && !this.data.storeId) {
      app.showToast('请选择门店');
      return;
    }

    if (!this.data.contactName) {
      app.showToast('请填写联系人');
      return;
    }

    if (!this.data.contactMobile) {
      app.showToast('请填写联系电话');
      return;
    }

    const day = this.data.days[this.data.currentDayIndex];
    const time = this.data.times[this.data.currentTimeIndex];
    const serviceTime = day.timestamp + time.hour * 3600;

    this.setData({ submitting: true });
    app.showLoading('提交中...');

    try {
      const res = await app.request({
        url: '/?c=order&a=create',
        method: 'POST',
        data: {
          service_id: this.data.serviceId,
          spec_id: this.data.specId,
          technician_id: this.data.technicianId,
          store_id: this.data.storeId,
          address_id: this.data.addressId,
          service_time: serviceTime,
          service_type: this.data.serviceType,
          dispatch_type: this.data.dispatchType,
          coupon_id: this.data.couponId,
          use_balance: this.data.useBalance,
          remark: this.data.remark,
          contact_name: this.data.contactName,
          contact_mobile: this.data.contactMobile
        }
      });

      app.hideLoading();
      
      if (res.data.pay_amount <= 0) {
        app.showToast('下单成功');
        setTimeout(() => {
          wx.redirectTo({
            url: `/pages/order/detail?id=${res.data.order_id}`
          });
        }, 1500);
      } else {
        wx.redirectTo({
          url: `/pages/pay/index?order_id=${res.data.order_id}`
        });
      }
    } catch (e) {
      console.error(e);
      this.setData({ submitting: false });
      app.hideLoading();
    }
  },

  formatTime(timestamp) {
    return util.formatDate(timestamp);
  }
});
