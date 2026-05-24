const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    addresses: [],
    selectMode: false,
    loading: true
  },

  onLoad(options) {
    this.setData({
      selectMode: options.select === '1'
    });
    this.loadAddresses();
  },

  onShow() {
    this.loadAddresses();
  },

  async loadAddresses() {
    this.setData({ loading: true });
    try {
      const res = await app.request({
        url: '/?c=user&a=addressList',
        method: 'GET'
      });

      this.setData({
        addresses: res.data || [],
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onAddAddress() {
    wx.navigateTo({
      url: '/pages/mine/address-edit',
      events: {
        addressSaved: (address) => {
          if (this.data.selectMode) {
            this.selectAndBack(address);
          }
        }
      }
    });
  },

  onEditAddress(e) {
    const address = e.currentTarget.dataset.address;
    wx.navigateTo({
      url: `/pages/mine/address-edit?id=${address.id}`,
      events: {
        addressSaved: (address) => {
          if (this.data.selectMode) {
            this.selectAndBack(address);
          }
        }
      }
    });
  },

  onDeleteAddress(e) {
    const address = e.currentTarget.dataset.address;
    wx.showModal({
      title: '删除地址',
      content: '确定要删除该地址吗？',
      success: async (res) => {
        if (res.confirm) {
          try {
            await app.request({
              url: '/?c=user&a=addressDelete',
              method: 'POST',
              data: {
                id: address.id
              }
            });
            app.showToast('删除成功');
            this.loadAddresses();
          } catch (e) {
            console.error(e);
          }
        }
      }
    });
  },

  async onSetDefault(e) {
    const address = e.currentTarget.dataset.address;
    if (address.is_default) return;

    try {
      await app.request({
        url: '/?c=user&a=addressDefault',
        method: 'POST',
        data: {
          id: address.id
        }
      });
      app.showToast('设置成功');
      this.loadAddresses();
    } catch (e) {
      console.error(e);
    }
  },

  onSelectAddress(e) {
    if (!this.data.selectMode) return;

    const address = e.currentTarget.dataset.address;
    this.selectAndBack(address);
  },

  selectAndBack(address) {
    const pages = getCurrentPages();
    const prevPage = pages[pages.length - 2];
    if (prevPage && prevPage.selectAddress) {
      prevPage.selectAddress(address);
    }
    wx.navigateBack();
  }
});
