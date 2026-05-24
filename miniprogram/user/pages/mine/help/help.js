const app = getApp();

Page({
  data: {
    helpList: [],
    loading: true,
    expandedId: null
  },

  onLoad() {
    this.loadHelpList();
  },

  async loadHelpList() {
    try {
      const res = await app.request({
        url: '/?c=help&a=list',
        method: 'GET'
      });
      this.setData({
        helpList: res.data || [],
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({
        helpList: [
          { id: 1, title: '如何预约服务？', content: '在首页选择服务项目，选择服务时间和服务人员，提交订单并支付即可完成预约。' },
          { id: 2, title: '如何取消订单？', content: '在订单详情页点击"取消订单"按钮，待服务开始前可免费取消，服务开始后取消将收取一定手续费。' },
          { id: 3, title: '如何申请退款？', content: '在订单详情页点击"申请退款"，填写退款原因后提交，客服会在1-3个工作日内处理。' },
          { id: 4, title: '优惠券如何使用？', content: '在提交订单时，系统会自动显示可用优惠券，选择要使用的优惠券即可抵扣相应金额。' },
          { id: 5, title: '余额如何充值？', content: '进入"我的-我的钱包-充值"，选择充值金额后完成支付即可。' },
          { id: 6, title: '如何成为服务人员？', content: '请联系客服申请成为服务人员，需要提交相关资质证明并通过审核。' }
        ],
        loading: false
      });
    }
  },

  onItemTap(e) {
    const id = e.currentTarget.dataset.id;
    this.setData({
      expandedId: this.data.expandedId === id ? null : id
    });
  },

  onContactService() {
    wx.navigateTo({
      url: '/pages/mine/service/service'
    });
  }
});
