const app = getApp();

Page({
  data: {
    packages: [],
    selectedPackageId: 0,
    selectedPackage: null,
    customAmount: 0,
    isCustom: false,
    payType: 1,
    userInfo: null,
    submitting: false
  },

  onLoad() {
    this.loadData();
  },

  async loadData() {
    try {
      const [packages, userInfo] = await Promise.all([
        app.request({ url: '/?c=user&a=rechargePackages' }),
        app.request({ url: '/?c=user&a=info' })
      ]);

      this.setData({
        packages: packages.data || [],
        userInfo: userInfo.data
      });
    } catch (e) {
      console.error(e);
    }
  },

  onSelectPackage(e) {
    const pkg = e.currentTarget.dataset.pkg;
    this.setData({
      selectedPackageId: pkg.id,
      selectedPackage: pkg,
      customAmount: 0,
      isCustom: false
    });
  },

  onCustomTap() {
    this.setData({
      selectedPackageId: 0,
      selectedPackage: null,
      isCustom: true
    });
  },

  onAmountInput(e) {
    const amount = parseFloat(e.detail.value) || 0;
    this.setData({
      customAmount: amount,
      selectedPackage: amount > 0 ? {
        id: 0,
        name: '自定义充值',
        amount: amount,
        gift_amount: 0,
        gift_level: 0
      } : null
    });
  },

  onPayTypeTap(e) {
    const type = e.currentTarget.dataset.type;
    this.setData({ payType: type });
  },

  async onRecharge() {
    if (this.data.submitting) return;

    const pkg = this.data.selectedPackage;
    if (!pkg || pkg.amount <= 0) {
      app.showToast('请选择充值金额');
      return;
    }

    this.setData({ submitting: true });
    app.showLoading('提交中...');

    try {
      const res = await app.request({
        url: '/?c=user&a=recharge',
        method: 'POST',
        data: {
          package_id: this.data.selectedPackageId,
          amount: pkg.amount,
          pay_type: this.data.payType
        }
      });

      app.hideLoading();
      
      if (res.data.pay_amount <= 0) {
        app.showToast('充值成功');
        setTimeout(() => {
          wx.navigateBack();
        }, 1500);
      } else if (this.data.payType === 1) {
        const payParams = res.data.pay_params;
        wx.requestPayment({
          timeStamp: payParams.timeStamp,
          nonceStr: payParams.nonceStr,
          package: payParams.package,
          signType: payParams.signType,
          paySign: payParams.paySign,
          success: () => {
            app.showToast('充值成功');
            setTimeout(() => {
              wx.navigateBack();
            }, 1500);
          },
          fail: () => {
            app.showToast('支付失败');
            this.setData({ submitting: false });
          }
        });
      } else {
        app.showToast('充值成功');
        setTimeout(() => {
          wx.navigateBack();
        }, 1500);
      }
    } catch (e) {
      console.error(e);
      this.setData({ submitting: false });
      app.hideLoading();
    }
  }
});
