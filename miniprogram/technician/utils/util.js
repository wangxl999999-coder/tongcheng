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
    8: '已退款'
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
    8: '#9e9e9e'
  };
  return colorMap[status] || '#999';
};

const calculateDistance = (lat1, lon1, lat2, lon2) => {
  const R = 6371;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  const distance = R * c;
  return distance.toFixed(2);
};

const formatMoney = amount => {
  return parseFloat(amount).toFixed(2);
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
  calculateDistance,
  formatMoney,
  debounce,
  throttle,
  getStorage,
  setStorage,
  removeStorage
};
