const formatTime = date => {
  if (!date) return '';
  const d = new Date(date);
  const year = d.getFullYear();
  const month = (d.getMonth() + 1).toString().padStart(2, '0');
  const day = d.getDate().toString().padStart(2, '0');
  const hour = d.getHours().toString().padStart(2, '0');
  const minute = d.getMinutes().toString().padStart(2, '0');
  return `${year}-${month}-${day} ${hour}:${minute}`;
};

const formatDate = date => {
  if (!date) return '';
  const d = new Date(date);
  const year = d.getFullYear();
  const month = (d.getMonth() + 1).toString().padStart(2, '0');
  const day = d.getDate().toString().padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const getStatusText = status => {
  const statusMap = {
    0: '待付款',
    1: '待派单',
    2: '待接单',
    3: '待服务',
    4: '服务中',
    5: '待支付差额',
    6: '已完成',
    7: '已取消',
    8: '已退款',
    9: '待核销'
  };
  return statusMap[status] || '未知';
};

const getStatusColor = status => {
  const colorMap = {
    0: '#ff9800',
    1: '#ff9800',
    2: '#2196f3',
    3: '#ff9800',
    4: '#4caf50',
    5: '#f44336',
    6: '#9e9e9e',
    7: '#9e9e9e',
    8: '#9e9e9e',
    9: '#9c27b0'
  };
  return colorMap[status] || '#999';
};

const formatMoney = amount => {
  return parseFloat(amount).toFixed(2);
};

const generateOrderNo = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = (now.getMonth() + 1).toString().padStart(2, '0');
  const day = now.getDate().toString().padStart(2, '0');
  const hour = now.getHours().toString().padStart(2, '0');
  const minute = now.getMinutes().toString().padStart(2, '0');
  const second = now.getSeconds().toString().padStart(2, '0');
  const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
  return `${year}${month}${day}${hour}${minute}${second}${random}`;
};

const debounce = (fn, delay = 300) => {
  let timer = null;
  return function(...args) {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
      fn.apply(this, args);
    }, delay);
  };
};

const throttle = (fn, delay = 300) => {
  let last = 0;
  return function(...args) {
    const now = Date.now();
    if (now - last > delay) {
      last = now;
      fn.apply(this, args);
    }
  };
};

const getStorage = (key, defaultValue = null) => {
  try {
    const value = wx.getStorageSync(key);
    return value || defaultValue;
  } catch (e) {
    return defaultValue;
  }
};

const setStorage = (key, value) => {
  try {
    wx.setStorageSync(key, value);
    return true;
  } catch (e) {
    return false;
  }
};

const removeStorage = key => {
  try {
    wx.removeStorageSync(key);
    return true;
  } catch (e) {
    return false;
  }
};

module.exports = {
  formatTime,
  formatDate,
  getStatusText,
  getStatusColor,
  formatMoney,
  generateOrderNo,
  debounce,
  throttle,
  getStorage,
  setStorage,
  removeStorage
};
