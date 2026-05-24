const app = getApp();

Page({
  data: {
    availableAmount: '0.00',
    withdrawAmount: '',
    withdrawType: 1,
    accountInfo: {
      bank_name: '',
      bank_account: '',
      account_name: '',
      alipay_account: '',
      wechat_account: ''
    },
    records: [],
    loading: true,
    submitting: false
  },

  onLoad() {
    this.loadData();
  },

  async loadData() {
    try {
      const res = await app.request({
        url: '/?c=technician&a=withdrawInfo',
        method: 'GET'
      });

      this.setData({
        availableAmount: res.data.available_amount || '0.00',
        accountInfo: res.data.account || this.data.accountInfo,
        records: res.data.records || [],
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onAmountInput(e) {
    this.setData({
      withdrawAmount: e.detail.value
    });
  },

  onTypeChange(e) {
    const type = parseInt(e.currentTarget.dataset.type);
    this.setData({
      withdrawType: type
    });
  },

  onAccountInput(e) {
    const field = e.currentTarget.dataset.field;
    this.setData({
      [`accountInfo.${field}`]: e.detail.value
    });
  },

  onSelectAll() {
    this.setData({
      withdrawAmount: this.data.availableAmount
    });
  },

  async onSubmit() {
    const amount = parseFloat(this.data.withdrawAmount);
    
    if (!amount || amount <= 0) {
      app.showToast('请输入提现金额');
      return;
    }

    if (amount > parseFloat(this.data.availableAmount)) {
      app.showToast('提现金额不能超过可提现金额');
      return;
    }

    if (amount < 10) {
      app.showToast('最低提现金额为10元');
      return;
    }

    if (this.data.withdrawType === 1 && !this.data.accountInfo.alipay_account) {
      app.showToast('请输入支付宝账号');
      return;
    }

    if (this.data.withdrawType === 2 && !this.data.accountInfo.wechat_account) {
      app.showToast('请输入微信账号');
      return;
    }

    if (this.data.withdrawType === 3) {
      if (!this.data.accountInfo.bank_name || !this.data.accountInfo.bank_account || !this.data.accountInfo.account_name) {
        app.showToast('请填写完整的银行卡信息');
        return;
      }
    }

    try {
      this.setData({ submitting: true });
      wx.showLoading({ title: '提交中...' });
      
      await app.request({
        url: '/?c=technician&a=withdraw',
        method: 'POST',
        data: {
          amount: amount,
          type: this.data.withdrawType,
          ...this.data.accountInfo
        }
      });

      wx.hideLoading();
      this.setData({ submitting: false });
      
      app.showToast('申请成功', 'success');
      
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    } catch (e) {
      wx.hideLoading();
      this.setData({ submitting: false });
      console.error(e);
    }
  }
});
