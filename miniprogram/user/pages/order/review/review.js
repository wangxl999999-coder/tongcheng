const app = getApp();

Page({
  data: {
    orderId: 0,
    order: null,
    rating: 5,
    ratingTypes: ['非常差', '差', '一般', '好', '非常好'],
    technicianRating: 5,
    content: '',
    images: [],
    isAnonymous: 0,
    submitting: false,
    loading: true
  },

  onLoad(options) {
    this.setData({
      orderId: parseInt(options.id) || 0
    });
    this.loadOrder();
  },

  async loadOrder() {
    try {
      const res = await app.request({
        url: '/?c=order&a=detail',
        method: 'GET',
        data: {
          id: this.data.orderId
        }
      });

      this.setData({
        order: res.data,
        loading: false
      });
    } catch (e) {
      console.error(e);
      this.setData({ loading: false });
    }
  },

  onRatingTap(e) {
    const rating = e.currentTarget.dataset.rating;
    this.setData({ rating: parseInt(rating) });
  },

  onTechnicianRatingTap(e) {
    const rating = e.currentTarget.dataset.rating;
    this.setData({ technicianRating: parseInt(rating) });
  },

  onContentInput(e) {
    this.setData({ content: e.detail.value });
  },

  onChooseImage() {
    const maxCount = 9 - this.data.images.length;
    if (maxCount <= 0) {
      app.showToast('最多上传9张图片');
      return;
    }

    wx.chooseMedia({
      count: maxCount,
      mediaType: ['image'],
      sourceType: ['album', 'camera'],
      sizeType: ['compressed'],
      success: async (res) => {
        const tempFiles = res.tempFiles;
        const images = [...this.data.images];
        
        app.showLoading('上传中...');
        try {
          for (const file of tempFiles) {
            const uploadRes = await app.uploadFile({
              url: '/?c=upload&a=image',
              filePath: file.tempFilePath,
              name: 'file'
            });
            images.push(uploadRes.data.url);
          }
          
          this.setData({ images });
          app.hideLoading();
        } catch (e) {
          console.error(e);
          app.hideLoading();
          app.showToast('上传失败');
        }
      }
    });
  },

  onPreviewImage(e) {
    const current = e.currentTarget.dataset.src;
    wx.previewImage({
      current,
      urls: this.data.images
    });
  },

  onDeleteImage(e) {
    const index = e.currentTarget.dataset.index;
    const images = this.data.images;
    images.splice(index, 1);
    this.setData({ images });
  },

  onAnonymousChange(e) {
    this.setData({ isAnonymous: e.detail.value ? 1 : 0 });
  },

  async onSubmit() {
    if (this.data.submitting) return;

    if (!this.data.content.trim()) {
      app.showToast('请输入评价内容');
      return;
    }

    this.setData({ submitting: true });
    app.showLoading('提交中...');

    try {
      await app.request({
        url: '/?c=order&a=review',
        method: 'POST',
        data: {
          id: this.data.orderId,
          rating: this.data.rating,
          technician_rating: this.data.technicianRating,
          content: this.data.content.trim(),
          images: this.data.images.join(','),
          is_anonymous: this.data.isAnonymous
        }
      });

      app.hideLoading();
      app.showToast('评价成功');
      
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    } catch (e) {
      console.error(e);
      this.setData({ submitting: false });
      app.hideLoading();
    }
  }
});
