const formatTime = date => {
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const day = date.getDate();
  const hour = date.getHours();
  const minute = date.getMinutes();
  const second = date.getSeconds();

  return `${[year, month, day].map(formatNumber).join('/')} ${[hour, minute, second].map(formatNumber).join(':')}`;
};

const formatNumber = n => {
  n = n.toString();
  return n[1] ? n : `0${n}`;
};

const formatDate = timestamp => {
  if (!timestamp) return '';
  const date = new Date(timestamp * 1000);
  return `${date.getFullYear()}-${formatNumber(date.getMonth() + 1)}-${formatNumber(date.getDate())}`;
};

const formatDateTime = timestamp => {
  if (!timestamp) return '';
  const date = new Date(timestamp * 1000);
  return `${date.getFullYear()}-${formatNumber(date.getMonth() + 1)}-${formatNumber(date.getDate())} ${formatNumber(date.getHours())}:${formatNumber(date.getMinutes())}`;
};

const formatMoney = amount => {
  return '¥' + parseFloat(amount).toFixed(2);
};

const getDistanceText = distance => {
  if (!distance) return '';
  if (distance < 1) {
    return (distance * 1000).toFixed(0) + 'm';
  }
  return distance.toFixed(2) + 'km';
};

const getWeekText = timestamp => {
  const weeks = ['日', '一', '二', '三', '四', '五', '六'];
  const date = new Date(timestamp * 1000);
  const weekDay = date.getDay();
  const today = new Date();
  const tomorrow = new Date(today.getTime() + 86400000);
  
  if (date.toDateString() === today.toDateString()) {
    return '今天';
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return '明天';
  }
  return '周' + weeks[weekDay];
};

const getDaysArray = (days = 7) => {
  const result = [];
  const today = new Date();
  for (let i = 0; i < days; i++) {
    const date = new Date(today.getTime() + i * 86400000);
    result.push({
      date: date,
      timestamp: Math.floor(date.getTime() / 1000),
      month: date.getMonth() + 1,
      day: date.getDate(),
      week: getWeekText(Math.floor(date.getTime() / 1000))
    });
  }
  return result;
};

const getTimesArray = (startHour = 8, endHour = 22) => {
  const result = [];
  for (let h = startHour; h < endHour; h++) {
    result.push({
      time: `${formatNumber(h)}:00`,
      hour: h,
      disabled: false
    });
  }
  return result;
};

const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

const throttle = (func, limit) => {
  let inThrottle;
  return function(...args) {
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
};

const deepClone = obj => {
  return JSON.parse(JSON.stringify(obj));
};

const getStorage = key => {
  try {
    return wx.getStorageSync(key);
  } catch (e) {
    return null;
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

const getRandomColor = () => {
  const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7', '#dfe6e9', '#a29bfe', '#fd79a8'];
  return colors[Math.floor(Math.random() * colors.length)];
};

const generateOrderNo = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = formatNumber(now.getMonth() + 1);
  const day = formatNumber(now.getDate());
  const hours = formatNumber(now.getHours());
  const minutes = formatNumber(now.getMinutes());
  const seconds = formatNumber(now.getSeconds());
  const random = Math.floor(Math.random() * 9000 + 1000);
  return `${year}${month}${day}${hours}${minutes}${seconds}${random}`;
};

const getDistance = (lat1, lng1, lat2, lng2) => {
  const radLat1 = lat1 * Math.PI / 180.0;
  const radLat2 = lat2 * Math.PI / 180.0;
  const a = radLat1 - radLat2;
  const b = lng1 * Math.PI / 180.0 - lng2 * Math.PI / 180.0;
  let s = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a / 2), 2) +
    Math.cos(radLat1) * Math.cos(radLat2) * Math.pow(Math.sin(b / 2), 2)));
  s = s * 6378.137;
  s = Math.round(s * 10000) / 10000;
  return s;
};

const hidePhone = phone => {
  if (!phone) return '';
  return phone.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2');
};

const hideIdCard = idCard => {
  if (!idCard) return '';
  return idCard.replace(/(\d{6})\d{8}(\d{4})/, '$1********$2');
};

const countDown = (endTime, callback) => {
  const timer = setInterval(() => {
    const now = Date.now();
    const diff = endTime - now;
    
    if (diff <= 0) {
      clearInterval(timer);
      callback && callback(0, 0, 0, 0);
      return;
    }
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    callback && callback(days, hours, minutes, seconds);
  }, 1000);
  
  return timer;
};

module.exports = {
  formatTime,
  formatNumber,
  formatDate,
  formatDateTime,
  formatMoney,
  getDistanceText,
  getWeekText,
  getDaysArray,
  getTimesArray,
  debounce,
  throttle,
  deepClone,
  getStorage,
  setStorage,
  removeStorage,
  getRandomColor,
  generateOrderNo,
  getDistance,
  hidePhone,
  hideIdCard,
  countDown
};
