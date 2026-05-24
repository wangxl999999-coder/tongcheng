const app = getApp();

Page({
  data: {
    orderId: 0,
    orderInfo: null,
    addonList: [],
    selectedAddons: [],
    totalAmount: 0,
    loading: true
  },

  onLoad(options) {
    this.setData({
      orderId: options.id || 0
    });
    this.loadOrderInfo();
    this.loadAddonList();
  },

  async loadOrderInfo() {
    try {
      const res = await app.request({
        url: '/?c=order&a=detail',
        method: 'GET',
        data: {
          id: this.data.orderId
        }
      });
      this.setData({
        orderInfo: res.data
      });
    } catch (e) {
      console.error(e);
      app.showToast('加载订单信息失败');
    }
  },

  async loadAddonList() {
    try {
      const res = await app.request({
        url: '/?c=service&a=addonList',
        method: 'GET',
        data: {
          order_id: this.data.orderId
        }
      });
      this.setData({
        addonList: res.data || [],
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
      app.showToast('加载加项列表失败');
    }
  },

  onAddonTap(e) {
    const id = e.currentTarget.dataset.id;
    const addonList = this.data.addonList;
    const selectedAddons = [...this.data.selectedAddons];
    
    const index = addonList.findIndex(item => item.id === id);
    if (index > -1) {
      addonList[index].selected = !addonList[index].selected;
      
      const selectedIndex = selectedAddons.findIndex(item => item.id === id);
      if (addonList[index].selected) {
        if (selectedIndex === -1) {
          selectedAddons.push(addonList[index]);
        }
      } else {
        if (selectedIndex > -1) {
          selectedAddons.splice(selectedIndex, 1);
        }
      }
      
      const totalAmount = selectedAddons.reduce((sum, item) => sum + parseFloat(item.price), 0);
      
      this.setData({
        addonList,
        selectedAddons,
        totalAmount: totalAmount.toFixed(2)
      });
    }
  },

  async onSubmit() {
    if (this.data.selectedAddons.length === 0) {
      app.showToast('请选择加项项目');
      return;
    }

    try {
      wx.showLoading({ title: '提交中...' });
      
      const res = await app.request({
        url: '/?c=order&a=addon',
        method: 'POST',
        data: {
          order_id: this.data.orderId,
          addon_ids: this.data.selectedAddons.map(item => item.id).join(','),
          amount: this.data.totalAmount
        }
      });

      wx.hideLoading();
      app.showToast('加项成功');
      
      setTimeout(() => {
        wx.redirectTo({
          url: `/pages/order/pay/pay?id=${res.data.order_id}&amount=${this.data.totalAmount}&type=addon`
        });
      }, 1500);
    } catch (e) {
      wx.hideLoading();
      console.error(e);
      app.showToast(e.msg || '提交失败');
    }
  }
});
