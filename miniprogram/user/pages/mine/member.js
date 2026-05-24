const app = getApp();
const util = require('../../utils/util.js');

Page({
  data: {
    userInfo: null,
    levels: [],
    currentLevel: null,
    nextLevel: null,
    progress: 0,
    privileges: [],
    loading: true
  },

  onLoad() {
    this.loadData();
  },

  onShow() {
    this.loadData();
  },

  async loadData() {
    this.setData({ loading: true });
    try {
      const [userRes, levelsRes] = await Promise.all([
        app.request({ url: '/?c=user&a=info' }),
        app.request({ url: '/?c=user&a=memberLevels' })
      ]);

      const userInfo = userRes.data;
      const levels = levelsRes.data || [];
      
      const currentLevel = levels.find(l => l.id === userInfo.level_id) || levels[0];
      const currentIndex = levels.findIndex(l => l.id === userInfo.level_id);
      const nextLevel = currentIndex < levels.length - 1 ? levels[currentIndex + 1] : null;
      
      let progress = 100;
      if (nextLevel) {
        const needConsume = nextLevel.need_consume - currentLevel.need_consume;
        const userConsume = userInfo.total_consume - currentLevel.need_consume;
        progress = Math.min(100, Math.max(0, (userConsume / needConsume) * 100));
      }

      const privileges = this.buildPrivileges(currentLevel);

      this.setData({
        userInfo,
        levels,
        currentLevel,
        nextLevel,
        progress,
        privileges,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  buildPrivileges(level) {
    return [
      { icon: '💎', name: '专属折扣', desc: `享受${level.discount / 10}折优惠`, enabled: level.discount < 100 },
      { icon: '🎁', name: '生日礼包', desc: '生日当月领取专属礼包', enabled: level.birthday_gift },
      { icon: '🎫', name: '专属优惠券', desc: '每月领取专属优惠券', enabled: level.monthly_coupon },
      { icon: '⚡', name: '优先派单', desc: '订单优先指派服务人员', enabled: level.priority_dispatch },
      { icon: '👑', name: '专属客服', desc: '一对一专属客服服务', enabled: level.exclusive_service },
      { icon: '📱', name: '积分双倍', desc: '消费积分双倍赠送', enabled: level.double_points }
    ];
  },

  onRecharge() {
    wx.navigateTo({
      url: '/pages/mine/recharge'
    });
  },

  onLevelTap(e) {
    const level = e.currentTarget.dataset.level;
    wx.showModal({
      title: level.name,
      content: `累计消费满${level.need_consume}元即可升级，享受${level.discount / 10}折优惠`,
      showCancel: false
    });
  },

  formatMoney(amount) {
    return util.formatMoney(amount);
  }
});
