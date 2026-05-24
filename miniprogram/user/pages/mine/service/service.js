const app = getApp();

Page({
  data: {
    serviceInfo: {
      phone: '400-123-4567',
      workTime: '周一至周日 9:00-21:00',
      wechat: 'tongcheng_service',
      qq: '123456789'
    },
    messageList: [],
    inputValue: '',
    scrollTop: 0
  },

  onLoad() {
    this.setData({
      messageList: [
        { type: 'service', content: '您好！欢迎咨询同城上门服务，请问有什么可以帮助您的？', time: '10:00' }
      ]
    });
  },

  onInput(e) {
    this.setData({
      inputValue: e.detail.value
    });
  },

  onSend() {
    if (!this.data.inputValue.trim()) {
      return;
    }

    const now = new Date();
    const time = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
    
    const newMessage = {
      type: 'user',
      content: this.data.inputValue,
      time: time
    };

    this.setData({
      messageList: [...this.data.messageList, newMessage],
      inputValue: '',
      scrollTop: this.data.messageList.length * 200
    });

    setTimeout(() => {
      const replyMessage = {
        type: 'service',
        content: '感谢您的咨询，客服正在为您处理，请稍候...',
        time: time
      };
      this.setData({
        messageList: [...this.data.messageList, replyMessage],
        scrollTop: this.data.messageList.length * 200
      });
    }, 1000);
  },

  onCallPhone() {
    wx.makePhoneCall({
      phoneNumber: this.data.serviceInfo.phone,
      fail: () => {
        app.showToast('拨号失败');
      }
    });
  },

  onCopyWechat() {
    wx.setClipboardData({
      data: this.data.serviceInfo.wechat,
      success: () => {
        app.showToast('微信号已复制');
      }
    });
  },

  onCopyQQ() {
    wx.setClipboardData({
      data: this.data.serviceInfo.qq,
      success: () => {
        app.showToast('QQ号已复制');
      }
    });
  }
});
