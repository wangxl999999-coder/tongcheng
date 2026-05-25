# API接口文档

## 接口说明

- **Base URL**: `http://your-domain.com/api`
- **请求格式**: `Content-Type: application/json`
- **返回格式**: JSON
- **认证方式**: JWT (Bearer Token)

### 统一返回格式

```json
{
  "code": 0,
  "msg": "success",
  "data": {}
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| code | int | 状态码，0表示成功，其他表示错误 |
| msg | string | 消息 |
| data | mixed | 返回数据 |

### 错误状态码

| 状态码 | 说明 |
|--------|------|
| 0 | 成功 |
| 400 | 参数错误 |
| 401 | 未登录或登录过期 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

### 接口分类

1. [用户认证接口](#1-用户认证接口)
2. [首页数据接口](#2-首页数据接口)
3. [服务相关接口](#3-服务相关接口)
4. [订单相关接口](#4-订单相关接口)
5. [用户中心接口](#5-用户中心接口)
6. [技师端接口](#6-技师端接口)
7. [商家端接口](#7-商家端接口)
8. [公共接口](#8-公共接口)

---

## 1. 用户认证接口

### 1.1 微信登录

**接口**: `POST /?c=login&a=wxLogin`

**描述**: 微信一键登录

**请求参数**:
```json
{
  "code": "wx_code"
}
```

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| code | string | 是 | wx.login获取的code |

**返回数据**:
```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "token": "jwt_token_string",
    "user": {
      "id": 1,
      "nickname": "用户昵称",
      "avatar": "头像地址",
      "phone": "手机号",
      "balance": "100.00",
      "member_level": 1,
      "member_name": "普通会员"
    }
  }
}
```

---

### 1.2 手机号登录

**接口**: `POST /?c=login&a=phoneLogin`

**描述**: 手机号验证码登录

**请求参数**:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 是 | 手机号 |
| code | string | 是 | 验证码 |

---

### 1.3 发送验证码

**接口**: `POST /?c=login&a=sendCode`

**描述**: 发送手机验证码

**请求参数**:
```json
{
  "phone": "13800138000"
}
```

---

### 1.4 绑定手机号

**接口**: `POST /?c=login&a=bindPhone`

**需要登录**: 是

**描述**: 绑定用户手机号

**请求参数**:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```

---

## 2. 首页数据接口

### 2.1 首页数据

**接口**: `GET /?c=index&a=index`

**需要登录**: 否

**描述**: 获取首页所有数据

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| city_id | int | 否 | 城市ID，默认当前城市 |
| lat | float | 否 | 纬度 |
| lng | float | 否 | 经度 |

**返回数据**:
```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "banners": [{"id": 1, "image": "...", "link": "..."}],
    "notices": [{"id": 1, "title": "...", "content": "..."}],
    "popup": {"id": 1, "type": 1, "image": "...", "content": "..."},
    "categories": [{"id": 1, "name": "...", "icon": "..."}],
    "hot_services": [{"id": 1, "name": "...", "price": "..."}],
    "nearby_technicians": [{"id": 1, "name": "...", "avatar": "..."}]
  }
}
```

---

### 2.2 获取轮播图

**接口**: `GET /?c=banner&a=lists`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| position | int | 否 | 位置：1首页 2分类页 |
| city_id | int | 否 | 城市ID |

---

### 2.3 获取公告列表

**接口**: `GET /?c=notice&a=lists`

**需要登录**: 否

---

### 2.4 获取弹窗配置

**接口**: `GET /?c=popup&a=getConfig`

**需要登录**: 否

---

## 3. 服务相关接口

### 3.1 服务分类列表

**接口**: `GET /?c=category&a=lists`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| city_id | int | 否 | 城市ID |

**返回数据**:
```json
{
  "code": 0,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "name": "家政服务",
      "icon": "...",
      "children": [
        {"id": 11, "name": "保洁", "icon": "..."}
      ]
    }
  ]
}
```

---

### 3.2 服务列表

**接口**: `GET /?c=service&a=lists`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| category_id | int | 否 | 分类ID |
| city_id | int | 否 | 城市ID |
| keyword | string | 否 | 搜索关键词 |
| sort | string | 否 | 排序：price销量 rating综合 |
| order | string | 否 | 排序方式：asc desc |
| page | int | 否 | 页码，默认1 |
| pageSize | int | 否 | 每页数量，默认10 |
| lat | float | 否 | 纬度（距离排序） |
| lng | float | 否 | 经度（距离排序） |

---

### 3.3 服务详情

**接口**: `GET /?c=service&a=detail`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 服务ID |

---

### 3.4 服务规格

**接口**: `GET /?c=service&a=specs`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| service_id | int | 是 | 服务ID |

---

### 3.5 热门服务

**接口**: `GET /?c=service&a=hot`

**需要登录**: 否

---

### 3.6 推荐服务

**接口**: `GET /?c=service&a=recommend`

**需要登录**: 否

---

## 4. 订单相关接口

### 4.1 创建订单

**接口**: `POST /?c=order&a=create`

**需要登录**: 是

**请求参数**:
```json
{
  "service_id": 1,
  "spec_id": 1,
  "appointment_time": "2026-05-25 14:00:00",
  "technician_id": 0,
  "address_id": 1,
  "coupon_id": 0,
  "use_balance": 1,
  "remark": "备注信息",
  "service_type": 1
}
```

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| service_id | int | 是 | 服务ID |
| spec_id | int | 否 | 规格ID |
| appointment_time | string | 是 | 预约时间 |
| technician_id | int | 否 | 技师ID，0表示不指定 |
| address_id | int | 是 | 地址ID |
| coupon_id | int | 否 | 优惠券ID |
| use_balance | int | 否 | 是否使用余额：0否1是 |
| remark | string | 否 | 订单备注 |
| service_type | int | 是 | 服务类型：1上门 2到店 |

---

### 4.2 订单列表

**接口**: `GET /?c=order&a=lists`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | int | 否 | 订单状态 |
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

---

### 4.3 订单详情

**接口**: `GET /?c=order&a=detail`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 订单ID |

---

### 4.4 取消订单

**接口**: `POST /?c=order&a=cancel`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 订单ID |
| reason | string | 否 | 取消原因 |

---

### 4.5 订单支付

**接口**: `POST /?c=order&a=pay`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 订单ID |
| pay_type | int | 是 | 支付方式：1微信 2余额 |

---

### 4.6 订单评价

**接口**: `POST /?c=order&a=review`

**需要登录**: 是

**请求参数**:
```json
{
  "order_id": 1,
  "rating": 5,
  "content": "评价内容",
  "images": ["url1", "url2"],
  "tags": ["专业", "准时"]
}
```

---

### 4.7 订单加项

**接口**: `POST /?c=order&a=addon`

**需要登录**: 是

**请求参数**:
```json
{
  "order_id": 1,
  "addon_ids": [1, 2, 3]
}
```

---

### 4.8 补差价

**接口**: `POST /?c=order&a=supplement`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |
| amount | float | 是 | 补差金额 |
| remark | string | 否 | 备注 |

---

### 4.9 根据订单号查询

**接口**: `GET /?c=order&a=queryByNo`

**需要登录**: 是（商家/技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_no | string | 是 | 订单号 |

---

### 4.10 订单核销

**接口**: `POST /?c=order&a=verify`

**需要登录**: 是（商家）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

---

## 5. 用户中心接口

### 5.1 用户信息

**接口**: `GET /?c=user&a=info`

**需要登录**: 是

---

### 5.2 更新用户信息

**接口**: `POST /?c=user&a=updateInfo`

**需要登录**: 是

**请求参数**:
```json
{
  "nickname": "新昵称",
  "avatar": "头像url",
  "gender": 1
}
```

---

### 5.3 地址列表

**接口**: `GET /?c=user&a=addressList`

**需要登录**: 是

---

### 5.4 新增地址

**接口**: `POST /?c=user&a=addAddress`

**需要登录**: 是

**请求参数**:
```json
{
  "name": "收件人",
  "phone": "13800138000",
  "province": "省",
  "city": "市",
  "district": "区",
  "address": "详细地址",
  "is_default": 1,
  "lat": 39.908823,
  "lng": 116.397470
}
```

---

### 5.5 更新地址

**接口**: `POST /?c=user&a=updateAddress`

**需要登录**: 是

**请求参数**: 同新增地址，增加id字段

---

### 5.6 删除地址

**接口**: `POST /?c=user&a=deleteAddress`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 地址ID |

---

### 5.7 设置默认地址

**接口**: `POST /?c=user&a=setDefaultAddress`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 地址ID |

---

### 5.8 优惠券列表

**接口**: `GET /?c=user&a=couponList`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | int | 否 | 状态：0未使用 1已使用 2已过期 |

---

### 5.9 领取优惠券

**接口**: `POST /?c=user&a=receiveCoupon`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| coupon_id | int | 是 | 优惠券ID |

---

### 5.10 口令领取优惠券

**接口**: `POST /?c=user&a=receiveByCode`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| code | string | 是 | 口令码 |

---

### 5.11 钱包信息

**接口**: `GET /?c=user&a=wallet`

**需要登录**: 是

---

### 5.12 充值

**接口**: `POST /?c=user&a=recharge`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| package_id | int | 否 | 充值套餐ID |
| amount | float | 否 | 自定义金额 |
| pay_type | int | 是 | 支付方式：1微信 |

---

### 5.13 充值套餐列表

**接口**: `GET /?c=user&a=rechargePackages`

**需要登录**: 是

---

### 5.14 钱包流水

**接口**: `GET /?c=user&a=walletLogs`

**需要登录**: 是

---

### 5.15 会员信息

**接口**: `GET /?c=user&a=memberInfo`

**需要登录**: 是

---

### 5.16 会员充值档位

**接口**: `GET /?c=user&a=memberPackages`

**需要登录**: 是

---

### 5.17 套餐卡列表

**接口**: `GET /?c=user&a=packageList`

**需要登录**: 是

---

### 5.18 套餐卡详情

**接口**: `GET /?c=user&a=packageDetail`

**需要登录**: 是

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 套餐卡ID |

---

## 6. 技师端接口

### 6.1 技师登录

**接口**: `POST /?c=technician&a=login`

**描述**: 技师手机号登录或微信登录

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 否 | 手机号 |
| code | string | 否 | 验证码或微信code |
| type | int | 是 | 登录类型：1手机号 2微信 |

---

### 6.2 技师信息

**接口**: `GET /?c=technician&a=info`

**需要登录**: 是（技师）

---

### 6.3 更新技师信息

**接口**: `POST /?c=technician&a=updateProfile`

**需要登录**: 是（技师）

**请求参数**:
```json
{
  "name": "姓名",
  "avatar": "头像",
  "phone": "手机号",
  "id_card": "身份证号",
  "skills": [1, 2, 3],
  "intro": "个人简介",
  "experience": 3
}
```

---

### 6.4 切换在线状态

**接口**: `POST /?c=technician&a=toggleOnline`

**需要登录**: 是（技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| online | int | 是 | 在线状态：0离线 1在线 |

---

### 6.5 抢单大厅列表

**接口**: `GET /?c=technician&a=grabList`

**需要登录**: 是（技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

---

### 6.6 抢单

**接口**: `POST /?c=technician&a=grabOrder`

**需要登录**: 是（技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

---

### 6.7 技师订单列表

**接口**: `GET /?c=technician&a=orderList`

**需要登录**: 是（技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | int | 否 | 订单状态 |
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

---

### 6.8 接单

**接口**: `POST /?c=technician&a=acceptOrder`

**需要登录**: 是（技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

---

### 6.9 开始服务

**接口**: `POST /?c=technician&a=startService`

**需要登录**: 是（技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

---

### 6.10 完成服务

**接口**: `POST /?c=technician&a=finishService`

**需要登录**: 是（技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |

---

### 6.11 技师设置

**接口**: `GET /?c=technician&a=getSettings`

**需要登录**: 是（技师）

---

### 6.12 保存技师设置

**接口**: `POST /?c=technician&a=saveSettings`

**需要登录**: 是（技师）

**请求参数**:
```json
{
  "auto_accept": 0,
  "service_range": 5,
  "work_days": [1, 2, 3, 4, 5],
  "work_start": "09:00",
  "work_end": "21:00",
  "notice_sound": 1,
  "notice_vibrate": 1
}
```

---

### 6.13 收入统计

**接口**: `GET /?c=technician&a=incomeStats`

**需要登录**: 是（技师）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| period | string | 否 | week/month/all |

---

### 6.14 提现信息

**接口**: `GET /?c=technician&a=withdrawInfo`

**需要登录**: 是（技师）

---

### 6.15 申请提现

**接口**: `POST /?c=technician&a=withdraw`

**需要登录**: 是（技师）

**请求参数**:
```json
{
  "amount": 100,
  "type": 1,
  "alipay_account": "xxx@alipay.com",
  "wechat_account": "wxid",
  "bank_name": "银行名称",
  "bank_account": "银行卡号",
  "account_name": "开户名"
}
```

---

## 7. 商家端接口

### 7.1 商家登录

**接口**: `POST /?c=merchant&a=login`

**描述**: 商家登录

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 否 | 手机号 |
| password | string | 否 | 密码 |
| code | string | 否 | 验证码或微信code |
| type | int | 是 | 登录类型：1密码 2验证码 3微信 |

---

### 7.2 发送验证码

**接口**: `POST /?c=merchant&a=sendCode`

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 是 | 手机号 |

---

### 7.3 商家数据统计

**接口**: `GET /?c=merchant&a=stats`

**需要登录**: 是（商家）

**返回数据**:
```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "todayOrders": 10,
    "todayAmount": "1500.00",
    "pendingOrders": 5,
    "pendingVerify": 3,
    "weekOrders": 50,
    "weekAmount": "8000.00"
  }
}
```

---

### 7.4 商家订单列表

**接口**: `GET /?c=order&a=merchantList`

**需要登录**: 是（商家）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | int | 否 | 订单状态 |
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

---

### 7.5 派单

**接口**: `POST /?c=order&a=dispatch`

**需要登录**: 是（商家/管理员）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_id | int | 是 | 订单ID |
| technician_id | int | 否 | 技师ID，不传则系统派单 |

---

### 7.6 核销记录

**接口**: `GET /?c=order&a=verifyList`

**需要登录**: 是（商家）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

---

### 7.7 门店详情

**接口**: `GET /?c=store&a=detail`

**需要登录**: 是（商家）

---

### 7.8 更新门店信息

**接口**: `POST /?c=store&a=update`

**需要登录**: 是（商家）

**请求参数**:
```json
{
  "name": "门店名称",
  "logo": "logo地址",
  "phone": "联系电话",
  "address": "门店地址",
  "business_hours": "09:00-21:00",
  "description": "门店简介",
  "status": 1
}
```

---

### 7.9 商家收入统计

**接口**: `GET /?c=merchant&a=incomeSummary`

**需要登录**: 是（商家）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| period | string | 否 | week/month/all |

---

### 7.10 商家收入明细

**接口**: `GET /?c=merchant&a=incomeRecords`

**需要登录**: 是（商家）

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| period | string | 否 | week/month/all |
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

---

## 8. 公共接口

### 8.1 城市列表

**接口**: `GET /?c=city&a=lists`

**需要登录**: 否

---

### 8.2 热门城市

**接口**: `GET /?c=city&a=hot`

**需要登录**: 否

---

### 8.3 技师列表

**接口**: `GET /?c=technician&a=lists`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| city_id | int | 否 | 城市ID |
| service_id | int | 否 | 服务ID |
| lat | float | 否 | 纬度 |
| lng | float | 否 | 经度 |
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

---

### 8.4 附近技师

**接口**: `GET /?c=technician&a=nearby`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| lat | float | 是 | 纬度 |
| lng | float | 是 | 经度 |
| city_id | int | 否 | 城市ID |
| limit | int | 否 | 返回数量，默认10 |

---

### 8.5 技师详情

**接口**: `GET /?c=technician&a=detail`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 技师ID |

---

### 8.6 技师评价列表

**接口**: `GET /?c=technician&a=reviews`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| technician_id | int | 是 | 技师ID |
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

---

### 8.7 技师档期

**接口**: `GET /?c=technician&a=schedule`

**需要登录**: 否

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| technician_id | int | 是 | 技师ID |
| date | string | 否 | 日期，默认今天 |

---

### 8.8 图片上传

**接口**: `POST /?c=upload&a=image`

**需要登录**: 是

**请求方式**: multipart/form-data

**请求参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| file | file | 是 | 图片文件 |

**返回数据**:
```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "url": "http://.../xxx.jpg",
    "size": 102400
  }
}
```

---

### 8.9 帮助中心

**接口**: `GET /?c=help&a=lists`

**需要登录**: 否

---

### 8.10 关于我们

**接口**: `GET /?c=page&a=about`

**需要登录**: 否

---

### 8.11 用户协议

**接口**: `GET /?c=page&a=agreement`

**需要登录**: 否

---

### 8.12 隐私政策

**接口**: `GET /?c=page&a=privacy`

**需要登录**: 否

---

## 附录

### 订单状态码

| 值 | 说明 |
|----|------|
| 0 | 待付款 |
| 1 | 待派单 |
| 2 | 待接单 |
| 3 | 待服务 |
| 4 | 服务中 |
| 5 | 待支付差额 |
| 6 | 已完成 |
| 7 | 已取消 |
| 8 | 已退款 |
| 9 | 待核销 |

### 支付方式

| 值 | 说明 |
|----|------|
| 1 | 微信支付 |
| 2 | 余额支付 |

### 服务类型

| 值 | 说明 |
|----|------|
| 1 | 上门服务 |
| 2 | 到店核销 |

### JWT认证说明

所有需要登录的接口必须在请求头中携带：
```
Authorization: Bearer {token}
```

token在登录接口返回，有效期为7天，过期需要重新登录。

### 错误响应示例

```json
{
  "code": 401,
  "msg": "登录已过期，请重新登录",
  "data": null
}
```
