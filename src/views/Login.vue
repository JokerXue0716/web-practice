<template>
  <div class="login-container">
    <div class="login-header">
      <!-- 登录标题区 -->
      <h2>欢迎登录</h2>
    </div>
    
    <!-- 用户名输入框 -->
    <div class="form-group">
      <label for="username">用户名</label>
      <!-- 
        v-model 双向数据绑定：
        1. 将输入框的值与组件的username数据属性绑定
        2. 输入框变化时自动更新username
        3. username变化时自动更新输入框显示
      -->
      <input type="text" id="username" v-model="username" class="form-control" placeholder="请输入用户名" />
    </div>
    
    <!-- 密码输入框 -->
    <div class="form-group">
      <label for="password">密码</label>
      <!-- 同上v-model绑定密码字段 -->
      <input type="password" id="password" v-model="password" class="form-control" placeholder="请输入密码" />
    </div>
    
    <!-- 登录按钮 -->
    <div class="text-center">
       <!-- 
        @click 事件绑定：
        1. 点击按钮时触发handleLogin方法
        2. 使用.prevent可以自动阻止默认事件（这里不需要）
      -->
      <button class="btn btn-block btn-lg" @click="handleLogin">登录</button>
    </div>

    <div class="login-footer">
      <p>没有账号？ <a href="/register">注册</a></p>
    </div>
  </div>
</template>

<script>
export default {
  // 组件数据定义
  data() {
    return {
      username: '',// 存储用户名输入
      password: ''// 存储密码输入
    };
  },
  methods: {
     /**
     * 登录处理方法
     * 功能：
     * 1. 设置登录状态到本地存储
     * 2. 跳转到首页
     * 注意：
     * - 实际项目应该先发送请求到后端验证
     * - 验证通过后再进行状态存储和跳转
     */
    handleLogin() {
       // 设置登录状态到localStorage
      // 实际项目应该使用更安全的方式存储token
      localStorage.setItem('isLoggedIn', 'true'); // 标记用户已登录
     
      // 使用Vue Router进行页面跳转
      // 跳转到 Home 页面
      // this.$router是Vue Router注入的实例
      this.$router.push('/home');
    }
  }
};
</script>

<style scoped>
/* 
  scoped 作用域样式：
  这些样式只作用于当前组件，不会影响其他组件
  原理是通过添加data-v-xxx属性实现样式隔离
*/

.login-container {
  width: 100%;
  max-width: 400px; /* 限制最大宽度 */
  margin: 0 auto; /* 水平居中 */
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* 添加阴影效果 */
}

.login-header h2 {
  text-align: center;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 15px; /* 表单组间距 */
}

label {
  display: block;/* 让label独占一行 */
  margin-bottom: 5px;
}

input.form-control {
  width: 100%;
  padding: 10px;
  margin-bottom: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 16px;
}

.text-center {
  text-align: center;
}

.btn {
  width: 100%;
  padding: 15px;
  background-color: #007bff;
  color: #fff;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
}

.btn:hover {
  background-color: #0056b3;/* 鼠标悬停颜色 */
}

.login-footer {
  text-align: center;
  margin-top: 20px;
}

.login-footer p {
  font-size: 14px;
}

.login-footer a {
  color: #007bff;
  text-decoration: none;
}

.login-footer a:hover {
  text-decoration: underline;
}
</style>