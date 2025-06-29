<template>
  <div id="app">
    <!-- 顶部导航栏 -->
    <el-header class="header">
      <div class="header-content">
        <!-- 系统标题 -->
        <div class="header-left">歌手巡演专辑管理系统</div>
        <!-- 用户信息 -->
        <div class="header-right">
          <div>0717</div>  <!-- 用户ID -->
          <div>joker</div> <!-- 用户名 -->
        </div>
      </div>
    </el-header>

    <!-- 主体布局 -->
    <el-container>
      <!-- 左侧边栏菜单 -->
      <el-aside width="200px" class="sidebar">
        <el-menu default-active="1" class="el-menu-vertical-demo">
          <!-- 巡演主菜单 -->
          <el-menu-item index="1" @click="toggleTour" class="menu-item">
            巡演
          </el-menu-item>
          <!-- 巡演子菜单（动态显示） -->
          <el-submenu v-if="isTourVisible" index="1-1" class="submenu">
            <template #title>巡演子菜单</template>
            <el-menu-item index="1-1-1" @click="goToFirstTour" class="menu-item">一巡</el-menu-item>
            <el-menu-item index="1-1-2" @click="goToSecondTour" class="menu-item">二巡</el-menu-item>
            <el-menu-item index="1-1-3" @click="goToThirdTour" class="menu-item">三巡</el-menu-item>
          </el-submenu>

          <!-- 专辑主菜单 -->
          <el-menu-item index="2" @click="toggleAlbum" class="menu-item">
            专辑
          </el-menu-item>
          <!-- 专辑子菜单（动态显示） -->
          <el-submenu v-if="isAlbumVisible" index="2-1" class="submenu">
            <template #title>专辑子菜单</template>
            <el-menu-item index="2-1-1" @click="goToPreviousDecade" class="menu-item">前十年</el-menu-item>
            <el-menu-item index="2-1-2" @click="goToNextDecade" class="menu-item">后十年</el-menu-item>
          </el-submenu>
        </el-menu>
      </el-aside>

      <!-- 主内容区（路由视图） -->
      <el-main class="main-content">
        <router-view />
      </el-main>
    </el-container>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue'; // 引入Vue组合式API
import { useRouter } from 'vue-router'; // 引入路由功能

export default defineComponent({
  name: 'App',
  setup() {
    const router = useRouter(); // 获取路由实例

    // 使用ref创建响应式变量，控制子菜单显示/隐藏
    const isTourVisible = ref(false); // 巡演子菜单显示状态
    const isAlbumVisible = ref(false); // 专辑子菜单显示状态

    // 切换巡演子菜单显示状态
    const toggleTour = () => {
      isTourVisible.value = !isTourVisible.value;
    };

    // 切换专辑子菜单显示状态
    const toggleAlbum = () => {
      isAlbumVisible.value = !isAlbumVisible.value;
    };

    // 路由跳转方法
    const goToFirstTour = () => router.push('/first-tour');
    const goToSecondTour = () => router.push('/second-tour');
    const goToThirdTour = () => router.push('/third-tour');
    const goToPreviousDecade = () => router.push('/previous-decade');
    const goToNextDecade = () => router.push('/next-decade');

    // 暴露给模板使用的数据和方法
    return {
      isTourVisible,
      isAlbumVisible,
      toggleTour,
      toggleAlbum,
      goToFirstTour,
      goToSecondTour,
      goToThirdTour,
      goToPreviousDecade,
      goToNextDecade
    };
  }
});
</script>

<style scoped>
/* 全局容器样式 */
#app {
  height: 100vh; /* 占据整个视口高度 */
  font-family: Arial, sans-serif; /* 设置全局字体 */
}

/* 顶部导航栏样式 */
.el-header {
  background-color: #ffb6c1; /* 粉色背景 */
  padding: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* 导航栏内容布局 */
.header-content {
  display: flex;
  width: 100%;
  align-items: center;
}

/* 系统标题样式 */
.header-left {
  font-size: 32px; /* 大号字体 */
  font-weight: bold;
  text-align: center;
  flex-grow: 1; /* 占据剩余空间 */
  display: flex;
  justify-content: center;
  align-items: center;
  color: #fff; /* 白色文字 */
}

/* 用户信息区域样式 */
.header-right {
  text-align: center;
  font-size: 18px;
  margin-left: auto; /* 靠右对齐 */
  color: #fff; /* 白色文字 */
}

/* 用户信息项间距 */
.header-right div {
  margin: 5px 0;
}

/* 侧边栏样式 */
.el-aside {
  background-color: #ffb6c1; /* 粉色背景 */
  padding-top: 20px;
}

/* 菜单整体样式 */
.el-menu-vertical-demo {
  width: 100%;
  padding-top: 20px;
  background-color: #ffb6c1; /* 粉色背景 */
  border: none; /* 去除边框 */
}

/* 菜单项基础样式 */
.el-menu-item, .el-submenu {
  background-color: #ffb6c1; /* 粉色背景 */
  color: #fff; /* 白色文字 */
}

/* 菜单项悬停效果 */
.el-menu-item:hover, .el-submenu:hover {
  background-color: #ff99b3; /* 更深的粉色 */
}

/* 主容器布局 */
.el-container {
  display: flex;
  background-color: #ffb6c1; /* 粉色背景 */
}

/* 主内容区样式 */
.el-main {
  background-color: #87ceeb; /* 天蓝色背景 */
  flex-grow: 1; /* 占据剩余空间 */
  padding: 20px;
}
</style>